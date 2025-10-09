<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DocsController extends Controller
{
    /**
     * Render the main public documentation overview.
     */
    public function overview(): View
    {
        return $this->renderMarkdown('Docs/README.md', 'Guía funcional SICEFA');
    }

    /**
     * Render the catalogue for all available modules.
     */
    public function modules(): View
    {
        return $this->renderMarkdown('Docs/modules.md', 'Catálogo funcional de módulos');
    }

    /**
     * Render the PTVENTA specific flow documentation.
     */
    public function ptventa(): View
    {
        return $this->renderMarkdown('Modules/PTVENTA/Docs/flow.md', 'Flujo del módulo PTVENTA');
    }

    /**
     * Convert a Markdown file into an HTML view response.
     */
    protected function renderMarkdown(string $relativePath, string $title): View
    {
        $fullPath = base_path($relativePath);

        if (! File::exists($fullPath)) {
            throw new NotFoundHttpException("No se encontró la documentación solicitada.");
        }

        $markdown = File::get($fullPath);

        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        $content = $converter->convert($markdown)->getContent();

        return view('docs.markdown', [
            'title' => $title,
            'content' => $content,
        ]);
    }
}
