<?php

declare(strict_types=1);

namespace PhelWebTests\ReleasesGenerator\Domain;

use PHPUnit\Framework\TestCase;
use PhelWeb\ReleasesGenerator\Application\GitHubReleasePagesGenerator;
use PhelWeb\ReleasesGenerator\Domain\Release;

final class GitHubReleasePagesGeneratorTest extends TestCase
{
    private GitHubReleasePagesGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new GitHubReleasePagesGenerator();
    }

    public function test_generate_minor_page_for_single_release(): void
    {
        $release = $this->makeRelease(
            tagName: 'v0.23.0',
            name: '0.23.0',
            body: "This is a great release with many improvements.\n\n## Changes\n- Feature 1\n- Feature 2",
            publishedAt: '2025-10-05T10:00:00Z',
        );

        $result = $this->generator->generateMinorPageContent([$release]);

        self::assertStringContainsString('title = "Release: 0.23"', $result);
        self::assertStringContainsString('date = 2025-10-05', $result);
        self::assertStringContainsString('slug = "0-23"', $result);
        self::assertStringContainsString('minor = "0.23"', $result);
        self::assertStringContainsString('headline_version = "0.23.0"', $result);
        self::assertStringContainsString('latest_version = "0.23.0"', $result);
        self::assertStringContainsString('versions = ["0.23.0"]', $result);
        self::assertStringContainsString('## 0.23.0', $result);
        self::assertStringContainsString('## Changes', $result);
        self::assertStringContainsString('description = "This is a great release with many improvements."', $result);
    }

    public function test_section_heading_uses_hyphen_separator_instead_of_en_dash(): void
    {
        $release = $this->makeRelease(
            tagName: 'v0.22.0',
            name: '0.22.0 – Metadata conditions',
            body: 'Notes.',
            publishedAt: '2025-09-23T10:00:00Z',
        );

        $result = $this->generator->generateMinorPageContent([$release]);

        self::assertStringContainsString('## 0.22.0 - Metadata conditions', $result);
        self::assertStringNotContainsString('–', $result);
    }

    public function test_slug_includes_name_suffix_when_present(): void
    {
        $release = $this->makeRelease(
            tagName: 'v0.34.0',
            name: '0.34.0 - Toolsmith',
            body: 'Notes',
            publishedAt: '2026-04-20T10:00:00Z',
        );

        $result = $this->generator->generateMinorPageContent([$release]);

        self::assertStringContainsString('slug = "0-34-toolsmith"', $result);
    }

    public function test_compute_slug_direct(): void
    {
        $named = $this->makeRelease(
            tagName: 'v0.34.0',
            name: '0.34.0 - Toolsmith',
            body: '',
            publishedAt: '2026-04-20T10:00:00Z',
        );
        $bare = $this->makeRelease(
            tagName: 'v0.33.0',
            name: '0.33.0',
            body: '',
            publishedAt: '2026-04-17T10:00:00Z',
        );

        self::assertSame('0-34-toolsmith', $this->generator->computeSlug([$named]));
        self::assertSame('0-33', $this->generator->computeSlug([$bare]));
    }

    public function test_aliases_redirect_legacy_urls(): void
    {
        $headline = $this->makeRelease(
            tagName: 'v0.34.0',
            name: '0.34.0 - Toolsmith',
            body: 'Notes',
            publishedAt: '2026-04-20T10:00:00Z',
        );
        $patch = $this->makeRelease(
            tagName: 'v0.34.1',
            name: '0.34.1',
            body: 'Patch',
            publishedAt: '2026-04-21T10:00:00Z',
        );

        $result = $this->generator->generateMinorPageContent([$headline, $patch]);

        self::assertStringContainsString('"/releases/release-v0-34-0/"', $result);
        self::assertStringContainsString('"/releases/release-v0-34-1/"', $result);
        self::assertStringContainsString('"/releases/v0-34/"', $result);
    }

    public function test_title_includes_headline_suffix_when_present(): void
    {
        $release = $this->makeRelease(
            tagName: 'v0.34.0',
            name: '0.34.0 - Toolsmith',
            body: 'Notes',
            publishedAt: '2026-04-20T10:00:00Z',
        );

        $result = $this->generator->generateMinorPageContent([$release]);

        self::assertStringContainsString('title = "Release: 0.34 - Toolsmith"', $result);
    }

    public function test_patches_render_before_headline_and_extra_lists_versions(): void
    {
        $headline = $this->makeRelease(
            tagName: 'v0.34.0',
            name: '0.34.0 - Toolsmith',
            body: 'Big release with many features.',
            publishedAt: '2026-04-20T10:00:00Z',
        );
        $patch1 = $this->makeRelease(
            tagName: 'v0.34.1',
            name: '0.34.1',
            body: 'Patch fix A.',
            publishedAt: '2026-04-21T10:00:00Z',
        );
        $patch2 = $this->makeRelease(
            tagName: 'v0.34.2',
            name: '0.34.2',
            body: 'Patch fix B.',
            publishedAt: '2026-04-22T10:00:00Z',
        );

        $result = $this->generator->generateMinorPageContent([$headline, $patch1, $patch2]);

        self::assertStringContainsString('versions = ["0.34.2", "0.34.1", "0.34.0"]', $result);
        self::assertStringContainsString('latest_version = "0.34.2"', $result);
        self::assertStringContainsString('headline_version = "0.34.0"', $result);
        self::assertStringContainsString('date = 2026-04-22', $result);

        $p2Pos = strpos($result, '## 0.34.2');
        $p1Pos = strpos($result, '## 0.34.1');
        $p0Pos = strpos($result, '## 0.34.0');

        self::assertNotFalse($p2Pos);
        self::assertNotFalse($p1Pos);
        self::assertNotFalse($p0Pos);
        self::assertLessThan($p1Pos, $p2Pos, 'Newest patch should appear first');
        self::assertLessThan($p0Pos, $p1Pos, 'Headline should appear last');
    }

    public function test_downloads_section_groups_assets_per_release(): void
    {
        $release = Release::fromArray([
            'tag_name' => 'v0.23.0',
            'name' => '0.23.0',
            'body' => 'Release notes',
            'published_at' => '2025-10-05T10:00:00Z',
            'html_url' => 'https://github.com/phel-lang/phel-lang/releases/tag/v0.23.0',
            'assets' => [
                [
                    'name' => 'phel.phar',
                    'browser_download_url' => 'https://github.com/phel-lang/phel-lang/releases/download/v0.23.0/phel.phar',
                    'size' => 1048576,
                ],
            ],
        ]);

        $result = $this->generator->generateMinorPageContent([$release]);

        self::assertStringContainsString('## Downloads', $result);
        self::assertStringContainsString('**v0.23.0**', $result);
        self::assertStringContainsString('[phel.phar](https://github.com/phel-lang/phel-lang/releases/download/v0.23.0/phel.phar) (1 MB)', $result);
    }

    public function test_escape_special_characters_in_title(): void
    {
        $release = $this->makeRelease(
            tagName: 'v0.14.0',
            name: '0.14.0 - phel\str library',
            body: 'Added phel\str library',
            publishedAt: '2024-05-24T10:00:00Z',
        );

        $result = $this->generator->generateMinorPageContent([$release]);

        self::assertStringContainsString('title = "Release: 0.14 - phel\\\\str library"', $result);
    }

    public function test_format_pr_references_as_links(): void
    {
        $release = $this->makeRelease(
            tagName: 'v0.31.0',
            name: '0.31.0',
            body: "## Added\n- Feature A (#1153)\n- Feature B (#1128, #1132, #1125)",
            publishedAt: '2026-04-03T10:00:00Z',
        );

        $result = $this->generator->generateMinorPageContent([$release]);

        self::assertStringContainsString('[#1153](https://github.com/phel-lang/phel-lang/pull/1153)', $result);
        self::assertStringContainsString('[#1128](https://github.com/phel-lang/phel-lang/pull/1128)', $result);
        self::assertStringContainsString('[#1132](https://github.com/phel-lang/phel-lang/pull/1132)', $result);
        self::assertStringContainsString('[#1125](https://github.com/phel-lang/phel-lang/pull/1125)', $result);
    }

    public function test_format_contributor_mentions_as_github_profile_links(): void
    {
        $release = $this->makeRelease(
            tagName: 'v0.34.1',
            name: '0.34.1',
            body: "## 👥 Contributors\n@Chemaclass @JesusValeraDev\n\nemail@example.com should not match",
            publishedAt: '2026-04-21T10:00:00Z',
        );

        $result = $this->generator->generateMinorPageContent([$release]);

        self::assertStringContainsString('[@Chemaclass](https://github.com/Chemaclass)', $result);
        self::assertStringContainsString('[@JesusValeraDev](https://github.com/JesusValeraDev)', $result);
        self::assertStringContainsString('email@example.com should not match', $result);
        self::assertStringNotContainsString('[@example]', $result);
    }

    public function test_bare_compare_url_becomes_labelled_link(): void
    {
        $release = $this->makeRelease(
            tagName: 'v0.18.0',
            name: '0.18.0',
            body: '**Full Changelog**: https://github.com/phel-lang/phel-lang/compare/v0.17.0...v0.18.0',
            publishedAt: '2025-06-18T10:00:00Z',
        );

        $result = $this->generator->generateMinorPageContent([$release]);

        self::assertStringContainsString(
            '**Full Changelog**: [v0.17.0...v0.18.0](https://github.com/phel-lang/phel-lang/compare/v0.17.0...v0.18.0)',
            $result,
        );
    }

    public function test_compare_url_already_inside_markdown_link_is_not_wrapped_again(): void
    {
        $release = $this->makeRelease(
            tagName: 'v0.19.1',
            name: '0.19.1',
            body: '**Full Changelog**: [v0.19.0…v0.19.1](https://github.com/phel-lang/phel-lang/compare/v0.19.0...v0.19.1)',
            publishedAt: '2025-08-03T10:00:00Z',
        );

        $result = $this->generator->generateMinorPageContent([$release]);

        self::assertStringContainsString(
            '**Full Changelog**: [v0.19.0…v0.19.1](https://github.com/phel-lang/phel-lang/compare/v0.19.0...v0.19.1)',
            $result,
        );
        self::assertStringNotContainsString('([v0.19.0', $result);
    }

    public function test_repo_relative_links_become_absolute_github_urls_at_release_tag(): void
    {
        $release = $this->makeRelease(
            tagName: 'v0.51.0',
            name: '0.51.0',
            body: "- See [ADR 0016](docs/adr/0016-bare-names.md).\n- And [guide](./docs/guide.md).",
            publishedAt: '2026-09-05T10:00:00Z',
        );

        $result = $this->generator->generateMinorPageContent([$release]);

        self::assertStringContainsString(
            '[ADR 0016](https://github.com/phel-lang/phel-lang/blob/v0.51.0/docs/adr/0016-bare-names.md)',
            $result,
        );
        self::assertStringContainsString(
            '[guide](https://github.com/phel-lang/phel-lang/blob/v0.51.0/docs/guide.md)',
            $result,
        );
    }

    public function test_absolute_root_and_anchor_links_are_left_untouched(): void
    {
        $release = $this->makeRelease(
            tagName: 'v0.51.0',
            name: '0.51.0',
            body: '[a](https://example.com/x) [b](/documentation/) [c](#usage) [d](mailto:hi@example.com)',
            publishedAt: '2026-09-05T10:00:00Z',
        );

        $result = $this->generator->generateMinorPageContent([$release]);

        self::assertStringContainsString(
            '[a](https://example.com/x) [b](/documentation/) [c](#usage) [d](mailto:hi@example.com)',
            $result,
        );
    }

    public function test_legacy_blog_release_links_map_to_release_pages(): void
    {
        $release = $this->makeRelease(
            tagName: 'v0.8.0',
            name: '0.8.0',
            body: 'See the [blog post](https://phel-lang.org/blog/release-0-8/) and [older](/blog/release-0-7/).',
            publishedAt: '2023-01-16T10:00:00Z',
        );

        $result = $this->generator->generateMinorPageContent(
            [$release],
            ['0.7' => '0-7-improved-core-library', '0.8' => '0-8-json-support'],
        );

        self::assertStringContainsString(
            'See the [blog post](/releases/0-8-json-support/) and [older](/releases/0-7-improved-core-library/).',
            $result,
        );
        self::assertStringNotContainsString('/blog/', $result);
    }

    public function test_bare_legacy_blog_release_url_becomes_link_to_release_page(): void
    {
        $release = $this->makeRelease(
            tagName: 'v0.17.0',
            name: '0.17.0',
            body: "> TL;DR: macros.\n\nhttps://phel-lang.org/blog/release-0-17/\n\n### Features",
            publishedAt: '2025-06-01T10:00:00Z',
        );

        $result = $this->generator->generateMinorPageContent([$release], ['0.17' => '0-17-macroverse']);

        self::assertStringContainsString("\n[Release 0.17](/releases/0-17-macroverse/)\n", $result);
        self::assertStringNotContainsString('/blog/', $result);
    }

    public function test_legacy_blog_release_link_falls_back_to_minor_alias_when_slug_unknown(): void
    {
        $release = $this->makeRelease(
            tagName: 'v0.9.0',
            name: '0.9.0',
            body: 'See the [blog post](https://phel-lang.org/blog/release-0-9/).',
            publishedAt: '2023-02-05T10:00:00Z',
        );

        $result = $this->generator->generateMinorPageContent([$release]);

        self::assertStringContainsString('[blog post](/releases/v0-9/)', $result);
    }

    public function test_extract_description_truncates_long_text(): void
    {
        $longText = str_repeat('This is a very long description. ', 20);
        $release = $this->makeRelease(
            tagName: 'v0.23.0',
            name: '0.23.0',
            body: $longText,
            publishedAt: '2025-10-05T10:00:00Z',
        );

        $result = $this->generator->generateMinorPageContent([$release]);

        self::assertStringContainsString('description =', $result);
        self::assertStringContainsString('...', $result);
    }

    public function test_extract_description_strips_inline_markdown(): void
    {
        $release = $this->makeRelease(
            tagName: 'v0.23.0',
            name: '0.23.0',
            body: 'New `phel eval` command, **faster** boot. See the [blog post](https://phel-lang.org/blog/x/) and `*program*`.',
            publishedAt: '2025-10-05T10:00:00Z',
        );

        $result = $this->generator->generateMinorPageContent([$release]);

        self::assertStringContainsString(
            'description = "New phel eval command, faster boot. See the blog post and *program*."',
            $result,
        );
    }

    public function test_extract_description_truncates_after_stripping_links(): void
    {
        $url = 'https://github.com/phel-lang/phel-lang/blob/master/CHANGELOG.md';
        $release = $this->makeRelease(
            tagName: 'v0.23.0',
            name: '0.23.0',
            body: str_repeat('Lots of small improvements. ', 5) . "See the [Changelog]({$url}) for more.",
            publishedAt: '2025-10-05T10:00:00Z',
        );

        $result = $this->generator->generateMinorPageContent([$release]);

        self::assertMatchesRegularExpression('/^description = "[^\n]*See the Changelog for more\."$/m', $result);
    }

    public function test_extract_description_strips_em_dashes(): void
    {
        $release = $this->makeRelease(
            tagName: 'v0.23.0',
            name: '0.23.0',
            body: 'Faster boot — and fewer surprises.',
            publishedAt: '2025-10-05T10:00:00Z',
        );

        $result = $this->generator->generateMinorPageContent([$release]);

        self::assertStringContainsString('description = "Faster boot, and fewer surprises."', $result);
    }

    public function test_empty_release_list_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->generator->generateMinorPageContent([]);
    }

    private function makeRelease(
        string $tagName,
        string $name,
        string $body,
        string $publishedAt,
    ): Release {
        return Release::fromArray([
            'tag_name' => $tagName,
            'name' => $name,
            'body' => $body,
            'published_at' => $publishedAt,
            'html_url' => "https://github.com/phel-lang/phel-lang/releases/tag/{$tagName}",
            'assets' => [],
        ]);
    }
}
