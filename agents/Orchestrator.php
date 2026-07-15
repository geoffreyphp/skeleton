<?php

namespace Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;
use Tools\WeatherTool;

class Orchestrator implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function instructions(): Stringable|string
    {
        $weatherBriefing = skill('weather-briefing');

        return <<<PROMPT
            Your name is Geoffrey a helpful and friendly assistant.

            {$weatherBriefing}
        PROMPT;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'score' => $schema->integer()->required(),
            'metadata' => $schema->object(fn ($schema) => [
                'confidence' => $schema->string()->enum(['low', 'medium', 'high'])->required(),
                'language' => $schema->string()->required(),
            ])->required(),
        ];
    }

    public function tools(): iterable
    {
        return [
            new WeatherTool,
        ];
    }
}