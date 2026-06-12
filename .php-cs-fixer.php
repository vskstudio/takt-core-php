<?php
return (new PhpCsFixer\Config())
    ->setRules(['@PSR12' => true, '@PHP81Migration' => true])
    ->setFinder(PhpCsFixer\Finder::create()->in([__DIR__ . '/src', __DIR__ . '/tests']));
