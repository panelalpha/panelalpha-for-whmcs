<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TrialEmailScriptTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../modules/addons/panelalpha/lib/TrialEmailScript.php';
    }

    public function testApostropheAndBacktickPayloadIsEncodedSafely(): void
    {
        $payload = "x'-alert`1`-'@a.co";
        $result = panelalpha_trial_email_prefill_script($payload);

        $this->assertStringNotContainsString("'-alert", $result);
        $this->assertStringNotContainsString("input.val('", $result);
        $this->assertStringContainsString('\u0027', $result);
    }

    public function testScriptTagPayloadIsEncodedSafely(): void
    {
        $payload = '"></script><script>alert(1)</script>"@a.co';
        $result = panelalpha_trial_email_prefill_script($payload);

        $this->assertStringNotContainsString('</script>', $result);
        $this->assertStringNotContainsString('<script', $result);
    }

    public function testNormalEmailPrefillsCheckoutField(): void
    {
        $email = 'user@example.com';
        $result = panelalpha_trial_email_prefill_script($email);

        $this->assertStringContainsString('input.val("user@example.com")', $result);
        $this->assertStringContainsString("form[action$='/cart.php?a=checkout'] input[name=email]", $result);
        $this->assertStringContainsString('if (input.length && !(input.val()))', $result);
    }
}
