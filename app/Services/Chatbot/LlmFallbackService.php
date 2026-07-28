<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gọi LLM API bên ngoài (Anthropic hoặc OpenAI) khi tầng rule-based không
 * nhận diện được ý định câu hỏi. LUÔN kèm theo ngữ cảnh dữ liệu THẬT (RAG
 * nhẹ) — danh sách sản phẩm/chính sách liên quan — để LLM chỉ diễn giải
 * chứ không tự bịa sản phẩm không có trong DB.
 *
 * Nếu chưa cấu hình CHATBOT_LLM_PROVIDER trong .env, trả về câu trả lời
 * mặc định an toàn thay vì gọi API (tránh lỗi khi chưa có key).
 */
class LlmFallbackService
{
    private ?string $provider;

    public function __construct()
    {
        $this->provider = config('chatbot.llm_provider');
    }

    public function isEnabled(): bool
    {
        return in_array($this->provider, ['anthropic', 'openai', 'gemini', 'groq'], true);
    }

    /**
     * @param string $userMessage Câu hỏi của khách
     * @param string $context     Ngữ cảnh dữ liệu thật (Global Context)
     * @param array  $history     Lịch sử chat dưới dạng mảng
     */
    public function reply(string $userMessage, string $context = '', array $history = []): string
    {
        if (!$this->isEnabled()) {
            return 'Xin lỗi, mình chưa hiểu rõ câu hỏi này. Bạn có thể mô tả cụ thể hơn '
                 . '(VD: loại sản phẩm, khoảng giá, thông số mong muốn), hoặc liên hệ hotline để được hỗ trợ trực tiếp.';
        }

       $systemPrompt = <<<PROMPT
Bạn là trợ lý ảo toàn năng của cửa hàng Electro Shop. Trả lời ngắn gọn, thân thiện, bằng tiếng Việt.
Bạn có khả năng tư vấn về sản phẩm, kiểm tra tình trạng đơn hàng, giải đáp chính sách (đổi trả, bảo hành, vận chuyển, thanh toán), và cung cấp mã giảm giá.
CHỈ được dựa vào NGỮ CẢNH DỮ LIỆU bên dưới để trả lời — không được tự bịa thêm thông tin không có thật.
Nếu khách hỏi về những vấn đề chung chung hoặc tâm sự, hãy giao tiếp tự nhiên và khéo léo dẫn dắt về các dịch vụ của cửa hàng.
Nếu khách hỏi những câu tiếp nối (ví dụ: "cái đầu tiên", "giá bao nhiêu", "hoàn tiền thì sao"), hãy xem lịch sử chat và ngữ cảnh để trả lời chính xác.
Nếu khách hỏi về mã giảm giá có thể áp dụng ngay, hãy lịch sự hỏi họ về giá trị đơn hàng dự kiến hoặc sản phẩm họ định mua để tư vấn mã phù hợp nhất (vì mã có điều kiện giá trị tối thiểu).
Nếu không có thông tin trong ngữ cảnh, hãy nói rõ là bạn chưa có thông tin đó và gợi ý khách liên hệ hotline.

NGỮ CẢNH DỮ LIỆU:
{$context}
PROMPT;

        try {
            return match ($this->provider) {
                'anthropic' => $this->callAnthropic($systemPrompt, $userMessage, $history),
                'openai'    => $this->callOpenAi($systemPrompt, $userMessage, $history),
                'gemini'    => $this->callGemini($systemPrompt, $userMessage, $history),
                'groq'      => $this->callGroq($systemPrompt, $userMessage, $history),
                default     => 'Xin lỗi, hiện chưa hỗ trợ được câu hỏi này.',
            };
        } catch (\Throwable $e) {
            Log::warning('Chatbot LLM fallback lỗi: ' . $e->getMessage());
            return 'Xin lỗi, hệ thống đang gặp sự cố khi xử lý câu hỏi này. Bạn vui lòng thử lại sau hoặc liên hệ hotline để được hỗ trợ.';
        }
    }

    private function callAnthropic(string $systemPrompt, string $userMessage, array $history): string
    {
        $apiKey = config('chatbot.anthropic.api_key');
        if (!$apiKey) {
            return 'Xin lỗi, chức năng trả lời mở rộng chưa được cấu hình (thiếu ANTHROPIC_API_KEY).';
        }
        
        $messages = [];
        foreach ($history as $h) {
            $messages[] = ['role' => $h['sender'] === 'bot' ? 'assistant' : 'user', 'content' => $h['message']];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $response = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(15)->post('https://api.anthropic.com/v1/messages', [
            'model'      => config('chatbot.anthropic.model'),
            'max_tokens' => 500,
            'system'     => $systemPrompt,
            'messages'   => $messages,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Anthropic API lỗi: ' . $response->status());
        }

        $blocks = $response->json('content', []);
        $text = collect($blocks)->where('type', 'text')->pluck('text')->implode("\n");

        return $text !== '' ? $text : 'Xin lỗi, mình chưa có câu trả lời phù hợp cho câu hỏi này.';
    }

    private function callOpenAi(string $systemPrompt, string $userMessage, array $history): string
    {
        $apiKey = config('chatbot.openai.api_key');
        if (!$apiKey) {
            return 'Xin lỗi, chức năng trả lời mở rộng chưa được cấu hình (thiếu OPENAI_API_KEY).';
        }
        
        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        foreach ($history as $h) {
            $messages[] = ['role' => $h['sender'] === 'bot' ? 'assistant' : 'user', 'content' => $h['message']];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $response = Http::withToken($apiKey)
            ->timeout(15)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'    => config('chatbot.openai.model'),
                'messages' => $messages,
                'max_tokens' => 500,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('OpenAI API lỗi: ' . $response->status());
        }

        return $response->json('choices.0.message.content', 'Xin lỗi, mình chưa có câu trả lời phù hợp cho câu hỏi này.');
    }

    private function callGemini(string $systemPrompt, string $userMessage, array $history): string
    {
        $apiKey = config('chatbot.gemini.api_key');
        if (!$apiKey) {
            return 'Xin lỗi, chức năng trả lời mở rộng chưa được cấu hình (thiếu GEMINI_API_KEY).';
        }

        $model = config('chatbot.gemini.model');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        
        $contents = [];
        foreach ($history as $h) {
            $contents[] = ['role' => $h['sender'] === 'bot' ? 'model' : 'user', 'parts' => [['text' => $h['message']]]];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];

        $response = Http::timeout(15)->post($url, [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => $contents,
        ]);

        if ($response->status() === 429) {
            Log::warning('Chatbot Gemini API vượt giới hạn quota (429): ' . $response->body());
            return 'Xin lỗi, mình đang xử lý hơi nhiều câu hỏi cùng lúc nên tạm thời quá tải. '
                 . 'Bạn vui lòng chờ khoảng 1 phút rồi hỏi lại nhé, hoặc liên hệ hotline để được hỗ trợ ngay.';
        }

        if (!$response->successful()) {
            throw new \RuntimeException('Gemini API lỗi: ' . $response->status() . ' - ' . $response->body());
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        return $text ?: 'Xin lỗi, mình chưa có câu trả lời phù hợp cho câu hỏi này.';
    }

    private function callGroq(string $systemPrompt, string $userMessage, array $history): string
    {
        $apiKey = config('chatbot.groq.api_key');
        if (!$apiKey) {
            return 'Xin lỗi, chức năng trả lời mở rộng chưa được cấu hình (thiếu GROQ_API_KEY).';
        }
        
        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        foreach ($history as $h) {
            $messages[] = ['role' => $h['sender'] === 'bot' ? 'assistant' : 'user', 'content' => $h['message']];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $response = Http::withToken($apiKey)
            ->timeout(15)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'    => config('chatbot.groq.model'),
                'messages' => $messages,
                'max_tokens' => 500,
            ]);

        if ($response->status() === 429) {
            Log::warning('Chatbot Groq API vượt giới hạn quota (429): ' . $response->body());
            return 'Xin lỗi, mình đang xử lý hơi nhiều câu hỏi cùng lúc nên tạm thời quá tải. '
                 . 'Bạn vui lòng chờ một chút rồi hỏi lại nhé.';
        }

        if (!$response->successful()) {
            throw new \RuntimeException('Groq API lỗi: ' . $response->status() . ' - ' . $response->body());
        }

        return $response->json('choices.0.message.content', 'Xin lỗi, mình chưa có câu trả lời phù hợp cho câu hỏi này.');
    }
}