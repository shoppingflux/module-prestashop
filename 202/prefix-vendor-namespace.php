<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL 202 ecommence
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL 202 ecommence is strictly forbidden.
 * In order to obtain a license, please contact us: tech@202-ecommerce.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe 202 ecommence
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL 202 ecommence est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter 202-ecommerce <tech@202-ecommerce.com>
 * ...........................................................................
 *
 * @author    202-ecommerce <tech@202-ecommerce.com>
 * @copyright Copyright (c) 202-ecommerce
 * @license   Commercial license
 */
if (!php_sapi_name() === 'cli') {
    return;
}
chdir(__DIR__);

/** @var string $namespaces_prefix the prefix to use */
$namespaces_prefix = 'SfGuzzle\\';

$dir_202 = './';
$vendor_dir = '../vendor/';
$dest_dir = '../vendor/prefixed/';

/** @var string $lib_to_prefix this library will have the prefix added to its declared namespaces */
$lib_to_prefix = 'guzzlehttp';

/** @var array $libs_to_update these libraries will have their used namespaces
 * updated with the prefixed version; the lib to prefix will automatically be added */
$libs_to_update = ['shoppingfeed'];

/**
 * Creates a path to a folder if it doesn't exist
 *
 * @param $folder
 */
function createFolder($folder)
{
    $tree = explode('/', $folder);
    $path = '';
    foreach ($tree as $item) {
        $path .= $item;
        if (!file_exists($path)) {
            mkdir($path);
        }
        $path .= '/';
    }
}

/** @var array $namespaces This array will contain the prefixed namespaces */
$namespaces = [];

// Get all files in the library to prefix
$allFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($vendor_dir . $lib_to_prefix));
$phpFiles = new RegexIterator($allFiles, '/\.php$/');

// For each file, prefix the namespaces
$namespaceRegex = "/(namespace\s+)(.+?);/";
$namespaceReplace = '$1' . $namespaces_prefix . '\\$2;';
foreach ($phpFiles as $phpFile) {
    $content = file_get_contents($phpFile->getRealPath());

    $matches = [];
    preg_match_all($namespaceRegex, $content, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        $namespaces[] = $match[2];
    }
    $namespaces = array_unique($namespaces);

    $newContent = preg_replace($namespaceRegex, $namespaceReplace, $content);

    // Save the modified files in the dest folder
    $destFilePath = $phpFile->getRealPath();
    $destFilePath = str_replace(realpath($vendor_dir) . '/', $dest_dir, $destFilePath);
    createFolder(dirname($destFilePath));

    file_put_contents($destFilePath, $newContent);
}

// Remove "leaves" from the namespace tree
$namespaces = array_values($namespaces);
for ($i = 0; $i < count($namespaces); ++$i) {
    if (!$namespaces[$i]) {
        continue;
    }

    for ($j = 0; $j < count($namespaces); ++$j) {
        if ($namespaces[$j] && $j != $i) {
            if (strpos($namespaces[$j], $namespaces[$i]) === 0) {
                $namespaces[$j] = false;
            }
        }
    }
}
$namespaces = array_filter($namespaces);

// For each library using the prefixed namespace
$libs_to_update[] = $lib_to_prefix;
foreach ($libs_to_update as $lib_to_update) {
    // Get all files, and prefix the namespaces where used
    if ($lib_to_prefix == $lib_to_update) {
        $allFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dest_dir . $lib_to_update));
    } else {
        $allFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($vendor_dir . $lib_to_update));
    }
    $phpFiles = new RegexIterator($allFiles, '/\.php$/');

    foreach ($phpFiles as $phpFile) {
        $content = file_get_contents($phpFile->getRealPath());
        $file_namespaces = $namespaces;
        $file_namespacesRegex = [];
        $file_namespacesReplace = [];

        if (preg_match("/namespace\s+(.+)\s*;/", $content, $matches)) {
            // If we have a "namespace" statement, don't replace in the code
            // unless there's a '\', but replace in the "use" statements
            $namespace_statement = $matches[1];
            if (($key = array_search($namespace_statement, $file_namespaces)) !== false) {
                $file_namespacesRegex[] = '/(use\s+)(' .
                    '(?:' . str_replace('\\', '\\\\', $namespace_statement) . ')'
                    . ')/';
                $file_namespacesReplace[] = '$1' . $namespaces_prefix . '\\$2';

                $file_namespacesRegex[] = '/(\s+\\\\)(' .
                    '(?:' . str_replace('\\', '\\\\', $namespace_statement) . ')'
                    . ')/';
                $file_namespacesReplace[] = '$1' . $namespaces_prefix . '\\$2';

                unset($file_namespaces[$key]);
            }
        }

        if (preg_match_all("/use\s+(.+)\s*;/", $content, $matches)) {
            // For each namespace to prefix
            foreach ($file_namespaces as $key => $file_namespace) {
                $exceptions = [];
                // For each "use" statement
                foreach ($matches[1] as $use_statement) {
                    // If the statement matches the namespace we're looking for
                    if (strpos($use_statement, $file_namespace) === 0) {
                        // Replace everywhere, except when matching the "use"
                        // statement
                        $exceptions[] = $use_statement;
                    }
                }

                // Replace the "use" statements
                $file_namespacesRegex[] = '/(use\s+)(' .
                    '(?:' . str_replace('\\', '\\\\', $file_namespace) . ')'
                    . ')/';
                $file_namespacesReplace[] = '$1' . $namespaces_prefix . '\\$2';

                if (!empty($exceptions)) {
                    // Do NOT replace the code using the "use" statements
                    $file_namespacesRegex[] = '/(\s+\\\\?)(' .
                        '(?:' . str_replace('\\', '\\\\', $file_namespace) . ')(?!'
                        . implode('|', array_map(function ($e) use ($file_namespace) {
                            if ($file_namespace == $e) {
                                return '';
                            }

                            return str_replace(['\\', $file_namespace], ['\\\\', ''], $e . '[^\\]');
                        }, $exceptions))
                        . '))/';
                    $file_namespacesReplace[] = '$1' . $namespaces_prefix . '\\$2';
                } else {
                    $file_namespacesRegex[] = '/(\s+\\\\?)(' .
                        '(?:' . str_replace('\\', '\\\\', $file_namespace) . ')'
                        . ')/';
                    $file_namespacesReplace[] = '$1' . $namespaces_prefix . '\\$2';
                }

                unset($file_namespaces[$key]);
            }
        }

        foreach ($file_namespaces as $file_namespace) {
            $file_namespacesRegex[] = '/(\s\\\\?)(' .
                        '(?:' . str_replace('\\', '\\\\', $file_namespace) . ')'
                        . ')/';
            $file_namespacesReplace[] = '$1' . $namespaces_prefix . '\\$2';
        }
        //$content = str_replace('<?php', "<?php/*\n".print_r($file_namespacesRegex, true).print_r($file_namespacesReplace, true)."\n*/" , $content);

        //$newContent = $content;
        $newContent = preg_replace($file_namespacesRegex, $file_namespacesReplace, $content);

        // Save the modified files in the dest folder
        $destFilePath = $phpFile->getRealPath();

        if ($lib_to_update != $lib_to_prefix) {
            $destFilePath = str_replace(realpath($vendor_dir) . '/', $dest_dir, $destFilePath);
            createFolder(dirname($destFilePath));
        }

        file_put_contents($destFilePath, $newContent);
    }

    // Move the library folder we just processed if necessary
    if ($lib_to_update != $lib_to_prefix) {
        rename($vendor_dir . $lib_to_update, $dir_202 . $lib_to_update);
    }
}

// Move the library folder with the prefixed namespaces
if (file_exists($vendor_dir . $lib_to_prefix)) {
    rename($vendor_dir . $lib_to_prefix, $dir_202 . $lib_to_prefix);
}
