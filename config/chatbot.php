<?php

return [
    // 'anthropic' | 'openai' | 'gemini' | 'groq' | null (null = tắt hẳn LLM fallback, chỉ dùng rule-based)
    'llm_provider' => env('CHATBOT_LLM_PROVIDER', null),

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model'   => env('CHATBOT_ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model'   => env('CHATBOT_OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model'   => env('CHATBOT_GEMINI_MODEL', 'gemini-2.0-flash'),
    ],

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'model'   => env('CHATBOT_GROQ_MODEL', 'llama-3.1-8b-instant'),
    ],

    // Số sản phẩm tối đa trả về trong 1 lần tìm kiếm
    'max_search_results' => 5,
];