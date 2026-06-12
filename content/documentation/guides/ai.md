+++
title = "AI Module"
weight = 7
description = "Provider-agnostic LLM client for Phel: chat, structured extraction, tool use, embeddings, and semantic search across Anthropic, OpenAI, and Voyage AI."
+++

`phel.ai` is a provider-agnostic client for LLM chat, structured extraction, tool use, embeddings, and semantic search. One API, swappable providers: pick the backend per call or globally.

| Provider | Chat | Tools | Embeddings |
|----------|------|-------|------------|
| `:anthropic` (default) | yes | yes | no |
| `:openai` | yes | yes | yes |
| `:voyageai` | no | no | yes |

For the full signature of every function mentioned here, see the [ai API reference](/documentation/reference/api/ai/). This page is the narrative overview.

## Quickstart

```phel skip
(ns my-app.main
  (:require phel.ai :as ai))

;; Either set env vars (ANTHROPIC_API_KEY, OPENAI_API_KEY, VOYAGE_API_KEY)
;; or configure explicitly:
(ai/configure {:api-key "sk-ant-..."})

(ai/complete "Say hi in one word")   ; => "Hi"
```

## Configuration

`configure` merges options into the shared `ai/config` atom.

| Key | Default | Purpose |
|-----|---------|---------|
| `:provider` | `:anthropic` | `:anthropic`, `:openai`, `:voyageai` |
| `:model` | `"claude-sonnet-4-6"` | Model name |
| `:max-tokens` | `1024` | Output token cap |
| `:api-key` | `nil` | Falls back to the provider env var |
| `:base-url` | `nil` | Override endpoint (proxies, self-hosted) |
| `:timeout` | `120` | HTTP timeout (seconds) |
| `:max-retries` | `2` | Retry 429/5xx with exponential backoff |

The default model evolves with `src/phel/ai.phel`; check there for the current value.

Every per-call `opts` map (`chat`, `complete`, `chat-with-tools`, `extract`, `extract-many`) accepts these same keys as per-request overrides:

```phel skip
(ai/complete "Summarize the news" {:provider :openai :model "gpt-4o-mini"})
```

For scoped config that auto-restores, even when the body throws, use `with-config`:

```phel skip
(ai/with-config {:provider :openai :model "gpt-4o"}
  (ai/complete "Summarize the news"))
;; global config restored here, even if the body threw
```

## Chat

```phel skip
(ai/chat [{:role "user" :content "What's 2+2?"}]
         {:system "Answer with a single integer."})
; => "4"
```

Multi-turn conversations carry history forward with `chat-with-history`:

```phel skip
(let [h1 (ai/chat-with-history [] "My name is Alice.")
      h2 (ai/chat-with-history h1 "What's my name?")]
  (get (last h2) :content))
; => "Alice"
```

## Structured extraction

Populate a schema from free text. `extract-many` returns a vector when the input describes multiple items:

```phel skip
(ai/extract
  {:name "string" :age "integer" :email "email address"}
  "Hi, I'm Alice, 30, alice@example.com")
; => {:name "Alice" :age 30 :email "alice@example.com"}
```

## Tool use

Define tools with the provider-agnostic `tool`, then either hand off to `run-tools` or drive the loop manually with `chat-with-tools` + `tool-result`.

```phel skip
(def tools
  [(ai/tool "get-weather"
            "Returns current weather for a city."
            {:city {:type "string" :description "City name"}})])

(def handlers
  {"get-weather" (fn [args] (str "72F sunny in " (get args :city)))})

(ai/run-tools [{:role "user" :content "weather in Paris?"}]
              tools handlers {:max-turns 5})
;; => "It's 72F and sunny in Paris."
```

`run-tools` sends the conversation, resolves each tool call via `handlers` (a map of tool name to function), feeds the results back, and stops when the model returns plain text or `:max-turns` is reached. Anthropic-only.

For finer control, drive `chat-with-tools` yourself:

```phel skip
(let [resp (ai/chat-with-tools messages tools)
      calls (ai/tool-calls resp)]
  ...)
```

`chat-with-tools` returns:

```phel skip
{:text       "..."    ; assistant text (nil if only tool calls)
 :tool-calls [{:name "..." :id "..." :input {...}}]
 :stop-reason "..."
 :raw        {...}}   ; full provider body
```

## Embeddings & semantic search

```phel skip
(ai/configure {:provider :openai})

(def index (ai/build-index ["cats purr" "dogs bark" "birds sing"]))
(ai/search "feline sounds" index {:k 1})
; => [{:text "cats purr" :embedding [...] :similarity 0.87}]
```

The vector-math primitives that power search are available for custom pipelines: `dot-product`, `magnitude`, `cosine-similarity`, and `nearest`. These are pure functions you can use without any network call:

```phel
(ns my-app.embed-demo
  (:require phel.ai :as ai))

(println (ai/dot-product [1 2 3] [4 5 6]))    # => 32
(println (ai/magnitude [3 4]))                # => 5
(println (ai/cosine-similarity [1 0] [1 0]))  # => 1
```

`nearest` ranks a query embedding against an index of `{:text "..." :embedding [...]}` maps and returns the top matches by descending similarity, the same shape `search` produces.

## Retry & timeouts

`:max-retries` (default `2`) retries HTTP 429 and 5xx responses with exponential backoff (500ms, 1s, 2s, ...). Network errors bubble up immediately. Tune per call:

```phel skip
(ai/complete "long task" {:timeout 300 :max-retries 4})
```

## Errors

All failures throw `\RuntimeException`. Messages include the HTTP status and the provider error body when available.

## Testing without a live API

`phel.ai` exposes an HTTP seam, `*http-post*`, that tests rebind to return canned responses. Combined with [`phel.mock`](/documentation/reference/api/mock/), this removes the dependency on a live provider:

```phel skip
(ns my-app.test.ai-test
  (:require phel.test :refer [deftest is])
  (:require phel.ai :as ai)
  (:require phel.mock :refer [mock-fn call-count first-call]))

(deftest test-my-ai-logic
  (let [fake (mock-fn (fn [_ _] {:status 200
                                 :body "{\"content\":[{\"type\":\"text\",\"text\":\"hi\"}]}"}))]
    (binding [ai/*http-post* fake]
      (is (= "hi" (ai/complete "say hi" {:api-key "k"})))
      (is (= 1 (call-count fake))))))
```

`phel.json` stringifies floats during `json/encode`. When a mock must return embedding arrays, build the response body as a raw JSON string instead of using `json/encode`.

## See also

- [ai API reference](/documentation/reference/api/ai/) - every function and its full signature
- [Agentic Coding](/documentation/reference/agentic-coding/) - using Phel with AI pair-programming tools
- Source: `src/phel/ai.phel`; tests: `tests/phel/ai.phel` in the [phel-lang](https://github.com/phel-lang/phel-lang) repo
