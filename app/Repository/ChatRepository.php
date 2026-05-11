<?php
namespace App\Repository;

/**
 * Menyimpan & membaca riwayat chat dari $_SESSION.
 * Satu-satunya tempat yang boleh menyentuh $_SESSION['chat'].
 */
class ChatRepository
{
    private string $model;

    public function __construct(string $model)
    {
        $this->model = $model;
    }

    // ----------------------------------------------------------------
    // Read
    // ----------------------------------------------------------------

    /** Ambil semua pesan untuk model ini */
    public function all(): array
    {
        return $_SESSION['chat'][$this->model] ?? [];
    }

    /** Bangun teks history untuk dikirim ke LLM sebagai konteks */
    public function toHistoryText(): string
    {
        $text = '';

        foreach ($this->all() as $msg) {

            if ($msg['role'] === 'user') {
                $text .= "User: {$msg['content']}\n";
                continue;
            }

            // pesan assistant dari DB mode
            if (($msg['type'] ?? '') === 'db') {
                $text .= "Assistant (DB): SQL: " . ($msg['sql'] ?? '') . "\n";

                if (!empty($msg['data'])) {
                    $sample = array_slice($msg['data'], 0, 5);
                    $text .= "Result: " . json_encode($sample) . "\n";
                }
                continue;
            }

            $text .= "Assistant: " . ($msg['content'] ?? '') . "\n";
        }

        return $text;
    }

    // ----------------------------------------------------------------
    // Write
    // ----------------------------------------------------------------

    public function addUserMessage(string $content): void
    {
        $_SESSION['chat'][$this->model][] = [
            'role'    => 'user',
            'content' => $content,
        ];
    }

    public function addAssistantMessage(string $content): void
    {
        $_SESSION['chat'][$this->model][] = [
            'role'    => 'assistant',
            'content' => $content,
        ];
    }

    /** Simpan hasil DB mode (sql + data rows) */
    public function addDbMessage(string $sql, array $data): void
    {
        $_SESSION['chat'][$this->model][] = [
            'role' => 'assistant',
            'type' => 'db',
            'sql'  => $sql,
            'data' => $data,
        ];
    }
    
    public function clear(): void
    {
        unset($_SESSION['chat'][$this->model]);
    }

    public function clearAll(): void
    {
        unset($_SESSION['chat']);
        unset($_SESSION['DEBUG']);
    }
}
