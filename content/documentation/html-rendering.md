+++
title = "HTML Rendering"
weight = 16
+++

Phel offers a template syntax based on Phel's data structures. It uses vectors to represent elements and maps to represent an element's attributes. All values are automatically escaped to provide better defense against cross-site scripting (XSS).

## Syntax

The `html` function in the module `phel\html` is the main function to generate HTML. See the following example:

```phel
(ns my-namespace
  (:require phel\html :refer [html]))

(html [:span {:class "foo"} "bar"])
# Evaluates to <span class="foo">bar</span>
```

The data structure that is accepted by `html` takes one of the following forms:

```phel
[tag body+]
[tag attributes body+]
```

The first item in the vector is a mandatory tag name. It can be either a keyword or a string. The second item is an optional map of attributes. All subsequent items in the vector are treated as the element body. This can include strings, nested vectors or lists.

```phel
(html [:div]) # Evaluates to "<div></div>"
(html ["div"]) # Evaluates to "<div></div>"
(html [:text "Lorem Ipsum"]) # Evaluates to "<text>Lorem Ipsum</text>"
(html [:body [:p] [:br]]) # Evaluates to "<body><p></p><br /></body>"
(html [:div {:id "foo"}]) # Evaluates to "<div id=\"foo\"></div>"
```

## Classes and Styles

A common need in building HTML templates is to adjust an element's class list and its inline styles. Therefore, Phel provides special enhancements for `class` and `style` attributes.

Instead of concatenating an inline style string, a map can be used. The next two examples evaluate to the same result.

```phel
(html [:div {:style "background:green;color:red;"} "bar"])
(html [:div {:style {:background "green" :color "red"}} "bar"])
# Both evaluate to
# "<div style=\"background:green;color:red;\">bar</div>"
```

Class lists can be built by vectors or maps. If a map is provided, the keys of the map are the class names, and the values are evaluated to true or false. Only keys with true values are added to the final class list.

```phel
(html [:div {:class [:a]}]) # <div class=\"a\"></div>
(html [:div {:class [:a "b"]}]) # <div class=\"a b\"></div>
(html [:div {:class [:a :b]}]) # <div class=\"a b\"></div>
(html [:div {:class {:a true :b false}}]) # <div class=\"a\"></div>
```

## Conditional rendering

To conditionally render parts of the HTML, the `if` expression can be used.

```phel
(html [:div [:p "a"] (if true [:p "b"] [:p "c"])])
# Evaluates to "<div><p>a</p><p>b</p></div>"
(html [:div [:p "a"] (if false [:p "b"] [:p "c"])])
# Evaluates to "<div><p>a</p><p>c</p></div>"
```

## List rendering

Similar to conditional rendering, the `for` expression can be used to render lists.

```phel
(html [:ul (for [i :range [0 3]] [:li i])])
# Evaluates to "<ul><li>0</li><li>1</li><li>2</li></ul>"
```

## Raw Html

By default, all values are automatically escaped to provide better defense against cross-site scripting (XSS). In order to output unescaped HTML, the `raw-string` function can be used.

```phel
(html [:span (raw-string "<a></a>")])
# Evaluates to "<span><a></a></span>"
```

## Doctypes

To add a doctype at the beginning of each element document, the `doctype` function can be used.

```phel
(html (doctype :html5) [:div])
# Evaluates to "<!DOCTYPE html>\n<div></div>"
```

The `doctype` function supports the following values: `:html5`, `:xhtml-transitional`, `:xhtml-strict` and `:html4`.
