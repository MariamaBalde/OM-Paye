<?php

namespace App\Http\Controllers;

use L5Swagger\Http\Controllers\SwaggerController as BaseSwaggerController;
use Illuminate\Http\Request;

class SwaggerController extends BaseSwaggerController
{
    /**
     * Generate URL for documentation file.
     *
     * @param  string  $documentation
     * @param  array  $config
     * @return string
     */
    protected function generateDocumentationFileURL(string $documentation, array $config)
    {
        $fileUsedForDocs = $config['paths']['docs_json'] ?? 'api-docs.json';

        if (! empty($config['paths']['format_to_use_for_docs'])
            && $config['paths']['format_to_use_for_docs'] === 'yaml'
            && $config['paths']['docs_yaml']
        ) {
            $fileUsedForDocs = $config['paths']['docs_yaml'];
        }

        $useAbsolutePath = config('l5-swagger.documentations.'.$documentation.'.paths.use_absolute_path', true);
        $docsRoute = $config['routes']['docs'] ?? 'docs';

        if ($useAbsolutePath) {
            return url($docsRoute . '/' . $fileUsedForDocs);
        }

        return '/' . $docsRoute . '/' . $fileUsedForDocs;
    }
}