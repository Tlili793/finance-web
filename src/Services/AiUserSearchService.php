<?php

namespace App\Services;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiUserSearchService
{
    private string $groqApiKey;
    private string $groqApiUrl;
    private string $groqModel;

    public function __construct(
        string $groqApiKey,
        string $groqApiUrl,
        string $groqModel,
        private readonly HttpClientInterface $httpClient
    ) {
        $this->groqApiKey = $groqApiKey;
        $this->groqApiUrl = $groqApiUrl;
        $this->groqModel  = $groqModel;
    }

    /**
     * Send natural language query to Groq and get back structured filters.
     * 
     * @return array{filters: array<string, mixed>, explanation: string, success: bool}
     */
    public function parseSearchQuery(string $naturalQuery): array
{
    $today = (new \DateTime())->format('Y-m-d');

    $systemPrompt = <<<PROMPT
You are a database query assistant for a user management system.
Today's date is {$today}.

The User entity has these fields:
- id (integer)
- name (string)
- email (string)
- roleId (integer: 1=admin, 2=regular user)
- isActive (boolean)
- isVerified (boolean)
- googleAccount (boolean: true if signed up via Google)
- lastLogin (datetime, can be null if never logged in)
- createdAt (datetime)
- phone (string, nullable)

Your job is to convert a natural language search query into a JSON filter object.

Return ONLY valid JSON with no explanation, no markdown, no backticks.
The JSON must have exactly two keys:
1. "filters" — an object with any of these optional keys:
   - isActive (boolean)
   - isVerified (boolean)
   - googleAccount (boolean)
   - roleId (integer: 1 or 2)
   - createdAfter (date string "Y-m-d")
   - createdBefore (date string "Y-m-d")
   - lastLoginBefore (date string "Y-m-d")
   - lastLoginAfter (date string "Y-m-d")
   - keyword (string for name/email search)
   - orderBy (one of: createdAt, lastLogin, name, email, id)
   - orderDir (ASC or DESC)

2. "explanation" — a short human-readable sentence describing what the search does.

Examples:
Query: "Show me inactive Google users"
Response: {"filters":{"isActive":false,"googleAccount":true},"explanation":"Showing inactive users who signed up via Google."}

Query: "Find users who signed up last month"
Response: {"filters":{"createdAfter":"2026-04-01","createdBefore":"2026-04-30","orderBy":"createdAt","orderDir":"DESC"},"explanation":"Showing users who registered during last month."}

Query: "List admins who haven't logged in recently"
Response: {"filters":{"roleId":1,"lastLoginBefore":"2026-04-04","orderBy":"lastLogin","orderDir":"ASC"},"explanation":"Showing admin accounts with no recent login activity."}

Query: "Show unverified accounts older than 7 days"
Response: {"filters":{"isVerified":false,"createdBefore":"2026-04-27","orderBy":"createdAt","orderDir":"ASC"},"explanation":"Showing unverified accounts created more than 7 days ago."}
PROMPT;

    try {
        // ── Step 1: Make the API call ─────────────────────────────────────
        $response = $this->httpClient->request('POST', $this->groqApiUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->groqApiKey,
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'model'       => $this->groqModel,
                'max_tokens'  => 300,
                'temperature' => 0,
                'messages'    => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $naturalQuery],
                ],
            ],
        ]);

        // ── Step 2: Parse the response ────────────────────────────────────
        $data    = $response->toArray(false);
        $content = $data['choices'][0]['message']['content'] ?? '';

        // Debug log — remove after confirming it works
        file_put_contents(
    __DIR__ . '/../../var/log/ai_search_debug.log',
    date('Y-m-d H:i:s') . ' STATUS: ' . $response->getStatusCode() . "\n" .
    'CONTENT: ' . $content . "\n" .
    'FULL RESPONSE: ' . json_encode($data) . "\n\n",
    FILE_APPEND
);

        // ── Step 3: Clean and decode JSON ─────────────────────────────────
        $content = trim(preg_replace('/```json|```/', '', $content));
        $parsed  = json_decode($content, true);

        if (!isset($parsed['filters'])) {
            return $this->fallback($naturalQuery);
        }

        return [
            'filters'     => $parsed['filters'],
            'explanation' => $parsed['explanation'] ?? 'Showing search results.',
            'success'     => true,
        ];

    } catch (\Throwable $e) {
        file_put_contents(
            __DIR__ . '/../../var/log/ai_search_debug.log',
            date('Y-m-d H:i:s') . ' ERROR: ' . $e->getMessage() . "\n\n",
            FILE_APPEND
        );
        return $this->fallback($naturalQuery);
    }
}

    /**
     * Fallback to basic keyword search if AI fails.
     * 
     * @return array{filters: array<string, mixed>, explanation: string, success: bool}
     */
    private function fallback(string $query): array
    {
        return [
            'filters'     => ['keyword' => $query],
            'explanation' => 'AI search unavailable — showing keyword results for "' . $query . '".',
            'success'     => false,
        ];
    }
   
}