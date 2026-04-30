+++
title = "HTML Rendering"
weight = 2
aliases = ["/documentation/html-rendering"]
+++

Template syntax based on Phel data structures. Vectors are elements, maps are attributes. Values auto-escape for XSS protection.

## Syntax

`html` from `phel\html` generates HTML:

```phel
(ns my-namespace
  (:require phel\html :refer [html]))

(html [:span {:class "foo"} "bar"])
;; Evaluates to <span class="foo">bar</span>
```

Forms:

```phel
[tag body+]
[tag attributes body+]
```

First item: tag name (keyword or string). Second item: optional attribute map. Rest: body (strings, nested vectors, lists).

```phel
(html [:div]) ; Evaluates to "<div></div>"
(html ["div"]) ; Evaluates to "<div></div>"
(html [:text "Lorem Ipsum"]) ; Evaluates to "<text>Lorem Ipsum</text>"
(html [:body [:p] [:br]]) ; Evaluates to "<body><p></p><br /></body>"
(html [:div {:id "foo"}]) ; Evaluates to "<div id=\"foo\"></div>"
```

## Classes and styles

Phel enhances `class` and `style` attributes.

Use a map for styles instead of a string. Both forms equivalent:

```phel
(html [:div {:style "background:green;color:red;"} "bar"])
(html [:div {:style {:background "green" :color "red"}} "bar"])
;; Both evaluate to
;; "<div style=\"background:green;color:red;\">bar</div>"
```

Class lists: vector or map. Map keys are class names; only truthy keys appear in the final list.

```phel
(html [:div {:class [:a]}]) ; <div class=\"a\"></div>
(html [:div {:class [:a "b"]}]) ; <div class=\"a b\"></div>
(html [:div {:class [:a :b]}]) ; <div class=\"a b\"></div>
(html [:div {:class {:a true :b false}}]) ; <div class=\"a\"></div>
```

## Conditional rendering

Use `if`:

```phel
(html [:div [:p "a"] (if true [:p "b"] [:p "c"])])
;; Evaluates to "<div><p>a</p><p>b</p></div>"
(html [:div [:p "a"] (if false [:p "b"] [:p "c"])])
;; Evaluates to "<div><p>a</p><p>c</p></div>"
```

## Rendering sequential data

`for` over vectors, lists, sets:

```phel
(html [:ul (for [i :range [0 3]] [:li i])])
;; Evaluates to "<ul><li>0</li><li>1</li><li>2</li></ul>"

(html [:ul (for [i :in [3 4 5]] [:li i])])
;; Evaluates to "<ul><li>3</li><li>4</li><li>5</li></ul>"
```

## Raw HTML

Values auto-escape for XSS protection. For unescaped output, use `raw-string`:

```phel
(html [:span (raw-string "<a></a>")])
;; Evaluates to "<span><a></a></span>"
```

## Doctypes

Use `doctype` for the document doctype:

```phel
(html (doctype :html5) [:div])
;; Evaluates to "<!DOCTYPE html>\n<div></div>"
```

Supported values: `:html5`, `:xhtml-transitional`, `:xhtml-strict`, `:html4`.
