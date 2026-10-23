<?php

/**
 * Builds the inline script that fills the checkout email field with the trial email.
 *
 * The email is JSON encoded with all HEX flags so it is safe inside a script tag.
 *
 * @param string $trialEmail Email address passed by PanelAlpha when upgrading a trial.
 * @return string JavaScript code without the script tag.
 */
function panelalpha_trial_email_prefill_script(string $trialEmail): string
{
    $encoded = json_encode($trialEmail, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

    return <<<HTML
jQuery(document).ready(function() {
    let input = jQuery("form[action$='/cart.php?a=checkout'] input[name=email]");
    if (input.length && !(input.val())) {
        input.val({$encoded});
    }
});
HTML;
}
