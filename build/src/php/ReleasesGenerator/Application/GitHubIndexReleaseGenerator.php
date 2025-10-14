<?php

declare(strict_types=1);

namespace PhelWeb\ReleasesGenerator\Application;

final readonly class GitHubIndexReleaseGenerator
{
    public function generateFrontMatterText(): string
    {
        return <<<'FRONTMATTER'
            +++
            title = "Phel Releases"
            sort_by = "date"
            template = "releases.html"
            page_template = "blog-entry.html"
            paginate_by = 15
            +++

            FRONTMATTER;
    }
}
