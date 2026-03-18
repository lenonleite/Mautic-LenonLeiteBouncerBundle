<?php

$localPhpStanConfigPath = __DIR__.'/../phpstan.neon';

if (!is_file($localPhpStanConfigPath)) {
    exit(0);
}

$localPhpStanConfig = file_get_contents($localPhpStanConfigPath);

if (false === $localPhpStanConfig) {
    throw new \RuntimeException('Unable to read phpstan.neon.');
}

$configParts = explode('###', $localPhpStanConfig);

if (3 !== count($configParts)) {
    exit(0);
}

$phpStanPatch = $configParts[2];
\assert(is_string($phpStanPatch));
$phpStanPatch = str_replace(
    ['    ', 'path: '],
    ["\t", 'path: plugins/LenonLeiteBouncerBundle/'],
    $phpStanPatch
);

$mauticPhpStanConfigPath = __DIR__.'/../../../phpstan.neon';
$mauticPhpStanConfig     = file_get_contents($mauticPhpStanConfigPath);

if (false === $mauticPhpStanConfig) {
    throw new \RuntimeException('Unable to read Mautic phpstan.neon.');
}

$phpStanPatch = \str_replace(
    'ignoreErrors:',
    'ignoreErrors:'.PHP_EOL.$phpStanPatch,
    $mauticPhpStanConfig
);

$result = file_put_contents($mauticPhpStanConfigPath, $phpStanPatch);

if (false === $result) {
    throw new \RuntimeException('Unable to write Mautic phpstan.neon.');
}

exit(0);
