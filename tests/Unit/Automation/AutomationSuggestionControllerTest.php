<?php

namespace Tests\Unit\Automation;

use Illuminate\Http\JsonResponse;
use Mockery;
use RuntimeException;
use Tests\TestCase;
use Vendor\Automation\Http\Controllers\AutomationSuggestionController;
use Vendor\Automation\Services\AutomationEngine;
use Vendor\Automation\Services\AutomationReconnectNotificationService;
use Vendor\Automation\Services\AutomationSuggestionPresenter;

class AutomationSuggestionControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_masks_sensitive_runtime_messages(): void
    {
        $controller = $this->makeController();

        $message = $controller->callSafeExceptionMessage(
            new RuntimeException('SQLSTATE[23000]: Integrity constraint violation in D:\\repo\\file.php on line 27')
        );

        $this->assertSame(
            "Une erreur inattendue est survenue pendant le traitement de l'automation.",
            $message
        );
    }

    public function test_it_keeps_expected_business_messages(): void
    {
        $controller = $this->makeController();

        $message = $controller->callSafeExceptionMessage(
            new RuntimeException('Google Calendar n est plus connecte pour ce tenant. Reconnectez Google Calendar puis relancez cette automation.')
        );

        $this->assertSame(
            'Google Calendar n est plus connecte pour ce tenant. Reconnectez Google Calendar puis relancez cette automation.',
            $message
        );
    }

    public function test_unexpected_exceptions_return_a_generic_500_response(): void
    {
        $controller = $this->makeController();

        $response = $controller->callErrorResponse(new \Exception('boom'));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(
            "Une erreur inattendue est survenue pendant le traitement de l'automation.",
            $response->getData(true)['message'] ?? null
        );
    }

    protected function makeController(): TestableAutomationSuggestionController
    {
        return new TestableAutomationSuggestionController(
            Mockery::mock(AutomationEngine::class),
            Mockery::mock(AutomationSuggestionPresenter::class),
            Mockery::mock(AutomationReconnectNotificationService::class),
        );
    }
}

class TestableAutomationSuggestionController extends AutomationSuggestionController
{
    public function callSafeExceptionMessage(\Throwable $e): string
    {
        return $this->safeExceptionMessage($e);
    }

    public function callErrorResponse(\Throwable $e): JsonResponse
    {
        return $this->errorResponse($e);
    }
}
