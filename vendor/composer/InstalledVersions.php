<?php

namespace Composer;

use Composer\Semver\VersionParser;






class InstalledVersions
{
private static $installed = array (
  'root' => 
  array (
    'pretty_version' => '1.0.0+no-version-set',
    'version' => '1.0.0.0',
    'aliases' => 
    array (
    ),
    'reference' => NULL,
    'name' => '__root__',
  ),
  'versions' => 
  array (
    '__root__' => 
    array (
      'pretty_version' => '1.0.0+no-version-set',
      'version' => '1.0.0.0',
      'aliases' => 
      array (
      ),
      'reference' => NULL,
    ),
    'graham-campbell/result-type' => 
    array (
      'pretty_version' => 'v1.1.4',
      'version' => '1.1.4.0',
      'aliases' => 
      array (
      ),
      'reference' => 'e01f4a821471308ba86aa202fed6698b6b695e3b',
    ),
    'phpoption/phpoption' => 
    array (
      'pretty_version' => '1.9.5',
      'version' => '1.9.5.0',
      'aliases' => 
      array (
      ),
      'reference' => '75365b91986c2405cf5e1e012c5595cd487a98be',
    ),
    'symfony/polyfill-ctype' => 
    array (
      'pretty_version' => 'v1.33.0',
      'version' => '1.33.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'a3cc8b044a6ea513310cbd48ef7333b384945638',
    ),
    'symfony/polyfill-mbstring' => 
    array (
      'pretty_version' => 'v1.33.0',
      'version' => '1.33.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '6d857f4d76bd4b343eac26d6b539585d2bc56493',
    ),
    'symfony/polyfill-php80' => 
    array (
      'pretty_version' => 'v1.33.0',
      'version' => '1.33.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '0cc9dd0f17f61d8131e7df6b84bd344899fe2608',
    ),
    'vlucas/phpdotenv' => 
    array (
      'pretty_version' => 'v5.6.3',
      'version' => '5.6.3.0',
      'aliases' => 
      array (
      ),
      'reference' => '955e7815d677a3eaa7075231212f2110983adecc',
    ),
  ),
);







public static function getInstalledPackages()
{
return array_keys(self::$installed['versions']);
}









public static function isInstalled($packageName)
{
return isset(self::$installed['versions'][$packageName]);
}














public static function satisfies(VersionParser $parser, $packageName, $constraint)
{
$constraint = $parser->parseConstraints($constraint);
$provided = $parser->parseConstraints(self::getVersionRanges($packageName));

return $provided->matches($constraint);
}










public static function getVersionRanges($packageName)
{
if (!isset(self::$installed['versions'][$packageName])) {
throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}

$ranges = array();
if (isset(self::$installed['versions'][$packageName]['pretty_version'])) {
$ranges[] = self::$installed['versions'][$packageName]['pretty_version'];
}
if (array_key_exists('aliases', self::$installed['versions'][$packageName])) {
$ranges = array_merge($ranges, self::$installed['versions'][$packageName]['aliases']);
}
if (array_key_exists('replaced', self::$installed['versions'][$packageName])) {
$ranges = array_merge($ranges, self::$installed['versions'][$packageName]['replaced']);
}
if (array_key_exists('provided', self::$installed['versions'][$packageName])) {
$ranges = array_merge($ranges, self::$installed['versions'][$packageName]['provided']);
}

return implode(' || ', $ranges);
}





public static function getVersion($packageName)
{
if (!isset(self::$installed['versions'][$packageName])) {
throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}

if (!isset(self::$installed['versions'][$packageName]['version'])) {
return null;
}

return self::$installed['versions'][$packageName]['version'];
}





public static function getPrettyVersion($packageName)
{
if (!isset(self::$installed['versions'][$packageName])) {
throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}

if (!isset(self::$installed['versions'][$packageName]['pretty_version'])) {
return null;
}

return self::$installed['versions'][$packageName]['pretty_version'];
}





public static function getReference($packageName)
{
if (!isset(self::$installed['versions'][$packageName])) {
throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}

if (!isset(self::$installed['versions'][$packageName]['reference'])) {
return null;
}

return self::$installed['versions'][$packageName]['reference'];
}





public static function getRootPackage()
{
return self::$installed['root'];
}







public static function getRawData()
{
return self::$installed;
}



















public static function reload($data)
{
self::$installed = $data;
}
}
