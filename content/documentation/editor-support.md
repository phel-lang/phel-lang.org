+++
title = "Editor Support"
weight = 111
+++

Phel integrates with popular editors through community-maintained plugins and
extensions. Install the tool that matches your workflow and point it at your
project directory.

## PhpStorm

Use the [Phel IntelliJ plugin](https://github.com/phel-lang/phel-intellij-plugin)
for syntax highlighting, structural editing, and REPL actions within PhpStorm or
other JetBrains IDEs. Install it via *Settings → Plugins → Marketplace* and
search for “Phel”.

## VSCode

The [Phel VS Code extension](https://github.com/phel-lang/phel-vs-code-extension)
adds syntax highlighting, snippets, and inline evaluation support. Install it
from the VS Code marketplace and reload the editor for the language features to
activate.

## Emacs

[interactive-lang-tools](https://codeberg.org/mmontone/interactive-lang-tools)
ships Phel support for Emacs, including editing helpers and REPL integration.
Follow the repository instructions to add it to your Emacs configuration, then
open a `.phel` file to enable the mode.

## Vim

[`phel.vim`](https://github.com/danirod/phel.vim) provides core editing
support—syntax highlighting, filetype detection, and indentation. Install it
through your plugin manager of choice:

```vim
Plug 'danirod/phel.vim'
```

Reload Vim and open a Phel file to confirm the highlighting is active.
