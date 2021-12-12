<?php
declare(strict_types=1);

namespace FroshPluginUploader\Components\PluginValidator\General;

use FroshPluginUploader\Components\Generation\ShopwareApp\App;
use FroshPluginUploader\Components\PluginValidator\ValidationInterface;
use FroshPluginUploader\Structs\ViolationContext;

class DescriptionLengthChecker implements ValidationInterface
{
    public function supports(ViolationContext $context): bool
    {
        return !($context->getPlugin() instanceof App);
    }

    public function validate(ViolationContext $context): void
    {
        $pluginReader = $context->getPlugin()->getReader();
        $violationMsg = 'The %s description should not be empty.';

        $lengthDescriptionGerman = mb_strlen($pluginReader->getDescriptionGerman(), 'UTF-8');
        if (empty($lengthDescriptionGerman)) {
            $context->addViolation(sprintf($violationMsg, 'German'));
        }

        $lengthDescriptionEnglish = mb_strlen($pluginReader->getDescriptionEnglish(), 'UTF-8');
        if (empty($lengthDescriptionEnglish)) {
            $context->addViolation(sprintf($violationMsg, 'English'));
        }
    }
}
