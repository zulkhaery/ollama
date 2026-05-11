<?php

namespace App\Controllers;

use App\Repository\ChatRepository;
use App\Services\OllamaService;
use App\Services\QdrantService;
use Database;

class RequestController
{
    private OllamaService $ollama;
    private QdrantService $qdrant;

    public function __construct()
    {
        $this->ollama   = new OllamaService();
        $this->qdrant  = new QdrantService();
    }

    public function index()
    {
        $question = $_POST['prompt'] ?? '';
        $model    = $_POST['model']  ?? '';

        if (empty($question)) return;

        $repo = new ChatRepository($model);

        try {
            $intent = $_POST['intent'];

            $answer = match ($intent) {
                'SQL'    => $this->handleSQLRoute($model, $question, $repo),
                'RAG'    => $this->handleRAGRoute($model, $question, $repo),
                'SEARCH' => $this->handleSearchRoute($model, $question, $repo),
                default  => $this->handleKnowledgeRoute($model, $question, $repo),
            };

            return $answer;

        } catch (\Exception $e) {
            echo 'ERROR: ' . $e->getMessage();
        }
    }

    private function handleKnowledgeRoute(string $model, string $question, ChatRepository $repo): string
    {
        $repo->addUserMessage($question);

        $config   = $this->getConfig()['KNOWLEDGE'];
        $messages = $this->buildMessages($question, $config, $repo);
        $answer   = $this->ollama->streamChat($model, $messages, $config['options']);

        $repo->addAssistantMessage($answer);

        return $answer;
    }

    private function handleSQLRoute(string $model, string $question, ChatRepository $repo): string
    {
        $config = $this->getConfig()['SQL'];
        $schema = $this->getDatabaseSchema();

        // Inject schema ke system prompt
        $systemPrompt = str_replace('{schema}', $schema, $config['system_prompt']);

        $messages = $this->buildMessages($question, $config, $repo, $systemPrompt);
        $rawSql   = $this->ollama->streamChat($model, $messages, $config['options'], echo: false);
        $sqlQuery = $this->cleanSqlOutput($rawSql);

        $repo->addUserMessage($question);
        $results = Database::query($sqlQuery);

        if (empty($results)) return "Data tidak ditemukan.";

        $repo->addDbMessage($sqlQuery, $results);

        return $this->renderSQLResult($sqlQuery, $results);
    }

    private function handleRAGRoute(string $model, string $question, ChatRepository $repo): string
    {
        $config = $this->getConfig()['RAG'];

        $repo->addUserMessage($question);

        // Search vector store
        $vector   = $this->ollama->embed($question);
        $contexts = $this->qdrant->search($vector, scoreThreshold: 0.6);

        if (empty($contexts)) return "Informasi tidak ditemukan di dokumen internal.";

        // Inject context ke system prompt
        $contextText  = implode("\n", array_column($contexts, 'text'));
        $systemPrompt = str_replace('{context}', $contextText, $config['system_prompt']);

        $messages = $this->buildMessages($question, $config, $repo, $systemPrompt);
        $answer   = $this->ollama->streamChat($model, $messages, $config['options']);


        $repo->addAssistantMessage($answer);

        return $answer;
    }

    private function handleSearchRoute(string $model, string $question, ChatRepository $repo): string
    {
        $repo->addUserMessage($question);

        $answer = "Fitur pencarian internet belum aktif.";

        $repo->addAssistantMessage($answer);

        return $answer;
    }

    // ------------------------------------------------------------------
    // MESSAGE BUILDER
    // ------------------------------------------------------------------

    /**
     * Membangun array messages dari history + pertanyaan saat ini.
     * Jika $systemPromptOverride diberikan (SQL/RAG), disisipkan sebagai pesan pertama.
     */
    private function buildMessages(
        string          $currentQuestion,
        array           $config,
        ChatRepository  $repo,
        ?string         $systemPromptOverride = null
    ): array {
        $messages = [];

        if ($systemPromptOverride) {
            $messages[] = ['role' => 'user', 'content' => $systemPromptOverride];
        }

        if ($config['use_history']) {
            $history = array_slice($repo->all(), -$config['history_limit']);

            foreach ($history as $msg) {
                $messages[] = $msg;
            }
        }

        $messages[] = ['role' => 'user', 'content' => $currentQuestion];

        return $messages;
    }

    // ------------------------------------------------------------------
    // DATABASE HELPERS
    // ------------------------------------------------------------------

    private function getDatabaseSchema(): string
    {
        $schema = '';
        $tables = Database::query('SHOW TABLES');

        foreach ($tables as $t) {
            $table   = array_values($t)[0];
            $cols    = Database::query("DESCRIBE $table");
            $schema .= "Table: $table\nColumns: "
                     . implode(', ', array_column($cols, 'Field'))
                     . "\n\n";
        }

        return $schema;
    }

    // ------------------------------------------------------------------
    // SQL RENDER HELPERS
    // ------------------------------------------------------------------

    private function cleanSqlOutput(string $rawSql): string
    {
        $cleaned = preg_replace('/```(?:sql)?\s*(.*?)\s*```/is', '$1', $rawSql);
        return trim(str_ireplace(['here is the query:', 'query:', 'sql:'], '', $cleaned));
    }

    private function renderSQLResult(string $rawSql, array $results): string
    {
        echo '<pre><code class="language-sql">' . $rawSql . '</code></pre>';

        if (count($results[0]) === 1) {
            echo array_values($results[0])[0];
        } else {
            echo "<table border='0' cellpadding='8' cellspacing='0' style='border-collapse:collapse;margin-top:10px;'>";
            echo "<tr>";
            foreach (array_keys($results[0]) as $column) {
                echo "<th>{$column}</th>";
            }
            echo "</tr>";

            foreach ($results as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>{$value}</td>";
                }
                echo "</tr>";
            }

            echo "</table>";
        }

        return '';
    }

    // ------------------------------------------------------------------
    // KONFIGURASI PUSAT
    // ------------------------------------------------------------------

    private function getConfig(): array
    {
        return [
            'KNOWLEDGE' => [
                'use_history'   => true,
                'history_limit' => 10,
                'options'       => ['temperature' => 0.7],
            ],
            'SQL' => [
                'use_history'   => true,
                'history_limit' => 3,
                'system_prompt' => "Buat MySQL query dari pertanyaan User. Schema:\n{schema}\n"
                                 . "Aturan:\n 1. Jawab hanya dengan mysql query.\n"
                                 . "2. Pastikan table dan kolom ada di schema.\n"
                                 . "3. Jawab HANYA SQL QUERY. Tanpa markdown. Tanpa penjelasan, HANYA MYSQL!",
                'options'       => ['temperature' => 0],
            ],
            'RAG' => [
                'use_history'   => true,
                'history_limit' => 10,
                'system_prompt' => '
                    Anda adalah asisten AI yang membantu menjawab pertanyaan pengguna.
                    Gunakan informasi berikut untuk menjawab pertanyaan:

                    {context}

                    Aturan:
                    - Jawab secara langsung, jelas, dan natural.
                    - Jangan menyebut sumber informasi.
                    - Jangan menyebut konteks, dokumen, teks, atau data di atas.
                    - Jangan mengatakan "berdasarkan informasi" atau kalimat serupa.
                    - Jika jawaban tidak tersedia, cukup jawab: "Saya tidak memiliki informasi untuk pertanyaan tersebut"',
                'options'       => ['temperature' => 0.2],
            ],
        ];
    }
}