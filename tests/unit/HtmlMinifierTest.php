<?php

declare(strict_types=1);

use App\Libraries\HtmlMinifier;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class HtmlMinifierTest extends CIUnitTestCase
{
    public function testRemovesIndentationAndComments(): void
    {
        $html = "<html>\n    <body>\n        <!-- note -->\n        <a>x</a>\n        <a>y</a>\n    </body>\n</html>\n";

        $this->assertSame("<html>\n<body>\n<a>x</a>\n<a>y</a>\n</body>\n</html>", HtmlMinifier::minify($html));
    }

    public function testKeepsWhitespaceWhereItMatters(): void
    {
        $blocks = [
            "<pre>\n  a\n    b\n</pre>",
            "<textarea name=\"n\">\n  keep\n</textarea>",
            "<script>\n  if (a) {\n    b();\n  }\n</script>",
            "<style>\n  .a { color: red; }\n</style>",
            "<p class=\"text-sm whitespace-pre-line\">line 1\n\n   line 2</p>",
        ];

        foreach ($blocks as $block) {
            $this->assertStringContainsString($block, HtmlMinifier::minify("<div>\n    " . $block . "\n</div>"));
        }
    }

    public function testKeepsConditionalComments(): void
    {
        $this->assertSame('<!--[if IE]>x<![endif]-->', HtmlMinifier::minify('<!--[if IE]>x<![endif]-->'));
    }
}
