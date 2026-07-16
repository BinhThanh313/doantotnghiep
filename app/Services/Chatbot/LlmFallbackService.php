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
     * @param string $context     Ngữ cảnh dữ liệu thật (VD: danh sách sản phẩm liên quan)
     */
    public function reply(string $userMessage, string $context = ''): string
    {
        if (!$this->isEnabled()) {
            return 'Xin lỗi, mình chưa hiểu rõ câu hỏi này. Bạn có thể mô tả cụ thể hơn '
                 . '(VD: loại sản phẩm, khoảng giá, thông số mong muốn), hoặc liên hệ hotline để được hỗ trợ trực tiếp.';
        }

       $systemPrompt = <<<PROMPT
Bạn là trợ lý mua sắm của cửa hàng Electro Shop. Trả lời ngắn gọn, thân thiện, bằng tiếng Việt.
CHỈ được nhắc tới sản phẩm/thông tin có trong phần NGỮ CẢNH DỮ LIỆU bên dưới — không được tự bịa
thêm sản phẩm, giá, hay thông số nào không có trong ngữ cảnh. Nếu ngữ cảnh không đủ thông tin để
trả lời, hãy nói rõ là chưa có thông tin và gợi ý khách hỏi cụ thể hơn hoặc liên hệ hotline.

QUAN TRỌNG: Phần "Danh sách sản phẩm nổi bật" trong NGỮ CẢNH DỮ LIỆU chỉ là dữ liệu DỰ PHÒNG để bạn
tư vấn KHI câu hỏi của khách thực sự liên quan tới mua sắm/sản phẩm nhưng không đủ rõ ràng (VD "có gì
hot không", "tư vấn giúp mình cái gì đó tốt"). Nếu câu hỏi của khách KHÔNG liên quan gì tới mua sắm,
sản phẩm, đơn hàng hay chính sách của shop (VD: hỏi thăm thời tiết, tâm sự, hỏi kiến thức chung, trò
chuyện phiếm...), TUYỆT ĐỐI KHÔNG được liệt kê hay gợi ý bất kỳ sản phẩm nào trong danh sách đó — chỉ
vì nó có sẵn trong ngữ cảnh không có nghĩa là nó liên quan tới câu hỏi. Trong trường hợp này, trả lời
ngắn gọn, thân thiện đúng vào chủ đề khách hỏi hoặc nói rõ đây không phải chuyên môn của bạn, rồi có
thể nhẹ nhàng hỏi khách có cần tư vấn mua sắm gì không — không được ép chuyển hướng sang liệt kê sản
phẩm nếu khách không hỏi.

QUAN TRỌNG: Nếu phần NGỮ CẢNH DỮ LIỆU có mục "Lịch sử hội thoại gần nhất", đây là các tin nhắn
trước đó của CHÍNH khách này. Khi khách hỏi câu so sánh/nối tiếp (VD: "cái nào tốt hơn", "cái nào
tốt nhất", "so sánh giúp tôi", "đứa nào trâu hơn"...) mà không nêu lại tên sản phẩm, BẮT BUỘC phải
hiểu là khách đang hỏi về các sản phẩm ĐÃ được nhắc tới trong lịch sử hội thoại đó — không được hỏi
lại khách "bạn quan tâm sản phẩm nào" nếu lịch sử đã có sẵn danh sách sản phẩm cụ thể.

QUAN TRỌNG: Câu hỏi mới của khách luôn là một góc nhìn/tiêu chí SO SÁNH CỤ THỂ khác với câu trước
(VD: từ "sản phẩm nào phù hợp" sang "cái nào pin trâu hơn"). TUYỆT ĐỐI không lặp lại y nguyên hoặc
gần như y nguyên nội dung bạn đã trả lời ở lượt trước — hãy trả lời thẳng vào đúng tiêu chí khách
vừa hỏi (VD: nếu hỏi về pin, chỉ so sánh thông số pin, không liệt kê lại toàn bộ cấu hình như lượt
trước). Nếu ngữ cảnh không có thông số khách hỏi (VD: không có dữ liệu pin), hãy nói thẳng là chưa
có thông tin đó, KHÔNG suy đoán hay dùng kiến thức chung ngoài ngữ cảnh để trả lời thay.

QUAN TRỌNG: Nếu danh sách sản phẩm trong NGỮ CẢNH DỮ LIỆU được đánh số (1., 2., 3...), thứ tự này
đúng theo thứ tự đã hiển thị/đã nhắc cho khách trước đó. Khi khách hỏi theo thứ tự (VD: "cái đầu
tiên", "cái thứ hai", "cái cuối cùng"), hãy map CHÍNH XÁC theo số thứ tự đó, không đoán theo tiêu
chí khác (giá, tên...).

NGỮ CẢNH DỮ LIỆU:
{$context}
PROMPT;

        try {
            return match ($this->provider) {
                'anthropic' => $this->callAnthropic($systemPrompt, $userMessage),
                'openai'    => $this->callOpenAi($systemPrompt, $userMessage),
                'gemini'    => $this->callGemini($systemPrompt, $userMessage),
                'groq'      => $this->callGroq($systemPrompt, $userMessage),
                default     => 'Xin lỗi, hiện chưa hỗ trợ được câu hỏi này.',
            };
        } catch (\Throwable $e) {
            Log::warning('Chatbot LLM fallback lỗi: ' . $e->getMessage());
            return 'Xin lỗi, hệ thống đang gặp sự cố khi xử lý câu hỏi này. Bạn vui lòng thử lại sau hoặc liên hệ hotline để được hỗ trợ.';
        }
    }

    private function callAnthropic(string $systemPrompt, string $userMessage): string
    {
        $apiKey = config('chatbot.anthropic.api_key');
        if (!$apiKey) {
            return 'Xin lỗi, chức năng trả lời mở rộng chưa được cấu hình (thiếu ANTHROPIC_API_KEY).';
        }

        $response = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(15)->post('https://api.anthropic.com/v1/messages', [
            'model'      => config('chatbot.anthropic.model'),
            'max_tokens' => 500,
            'system'     => $systemPrompt,
            'messages'   => [
                ['role' => 'user', 'content' => $userMessage],
            ],
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Anthropic API lỗi: ' . $response->status());
        }

        $blocks = $response->json('content', []);
        $text = collect($blocks)->where('type', 'text')->pluck('text')->implode("\n");

        return $text !== '' ? $text : 'Xin lỗi, mình chưa có câu trả lời phù hợp cho câu hỏi này.';
    }

    private function callOpenAi(string $systemPrompt, string $userMessage): string
    {
        $apiKey = config('chatbot.openai.api_key');
        if (!$apiKey) {
            return 'Xin lỗi, chức năng trả lời mở rộng chưa được cấu hình (thiếu OPENAI_API_KEY).';
        }

        $response = Http::withToken($apiKey)
            ->timeout(15)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'    => config('chatbot.openai.model'),
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'max_tokens' => 500,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('OpenAI API lỗi: ' . $response->status());
        }

        return $response->json('choices.0.message.content', 'Xin lỗi, mình chưa có câu trả lời phù hợp cho câu hỏi này.');
    }

    private function callGemini(string $systemPrompt, string $userMessage): string
    {
        $apiKey = config('chatbot.gemini.api_key');
        if (!$apiKey) {
            return 'Xin lỗi, chức năng trả lời mở rộng chưa được cấu hình (thiếu GEMINI_API_KEY).';
        }

        $model = config('chatbot.gemini.model');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::timeout(15)->post($url, [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $userMessage]]],
            ],
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

    private function callGroq(string $systemPrompt, string $userMessage): string
    {
        $apiKey = config('chatbot.groq.api_key');
        if (!$apiKey) {
            return 'Xin lỗi, chức năng trả lời mở rộng chưa được cấu hình (thiếu GROQ_API_KEY).';
        }

        $response = Http::withToken($apiKey)
            ->timeout(15)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'    => config('chatbot.groq.model'),
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
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