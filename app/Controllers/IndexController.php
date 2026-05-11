<?php

namespace App\Controllers;

use App\Repository\ChatRepository;

class IndexController
{
    public function index()
    {
      
        $cfg = require __DIR__ . '/../../config/model.php'; 
        $validModels = $cfg['list'];
        $defaultModel = $cfg['default'];
        $model = $_GET['model'] ?? $defaultModel;
        $repo = new ChatRepository($model);
        $chatHistory   = $repo->all();

     
        $data = [
            'validModels' => $validModels,
            'model' => $model,
            'chatHistory' => $chatHistory
        ];
        
        return view('chat', $data);
    }
}