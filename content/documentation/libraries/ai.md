+++
title = "AI Module"
weight = 3
description = "Provider-agnostic LLM client for Phel: chat, structured extraction, tool use, embeddings, and semantic search across Anthropic, OpenAI, and Voyage AI."
aliases = ["/documentation/guides/ai"]
+++

After this page you can call an LLM from Phel: ask a question, pull structured data out of text, let the model call your functions, and search texts by meaning. `phel.ai` ships with Phel and has one API for several providers.

| Provider | Chat | Tools | Embeddings |
|----------|------|-------|------------|
| `:anthropic` (default) | yes | yes | no |
| `:openai` | yes | yes | yes |
| `:voyageai` | no | no | yes |

The calls below need an API key and network access, so their results are examples, not fixed values.

## Ask a question

Set the key for your provider in an environment variable (`ANTHROPIC_API_KEY`, `OPENAI_API_KEY`, or `VOYAGE_API_KEY`), or pass it to `configure`. Then call `complete` with a prompt:

<!-- phel-test: skip -->
```phel
(ns my-app.main
  (:require phel.ai :as ai))

(ai/configure {:api-key "sk-ant-..."})

(ai/complete "Say hi in one word") ; => "Hi"
```

For more control, `chat` takes a vector of messages and a system prompt:

<!-- phel-test: skip -->
```phel
(ai/chat [{:role "user" :content "What's 2+2?"}]
         {:system "Answer with a single integer."})
; => "4"
```

`chat-with-history` keeps a conversation going. It returns the history with the new question and answer appended:

<!-- phel-test: skip -->
```phel
(let [h1 (ai/chat-with-history [] "My name is Alice.")
      h2 (ai/chat-with-history h1 "What's my name?")]
  (get (last h2) :content))
; => "Alice"
```

## Extract structured data

`extract` fills a map from free text. Each key of the schema map is an output field, and its value tells the model what to put there. `extract-many` returns a vector of maps when the text describes several items:

<!-- phel-test: skip -->
```phel
(ai/extract
  {:name "string" :age "integer" :email "email address"}
  "Hi, I'm Alice, 30, alice@example.com")
; => {:name "Alice" :age 30 :email "alice@example.com"}

(ai/extract-many {:name "string" :role "string"} "Alice is CEO, Bob is CTO")
; => [{:name "Alice" :role "CEO"} {:name "Bob" :role "CTO"}]
```

To check the result, validate it with [Schema](/documentation/libraries/schema/).

## Let the model call functions

Describe each tool with `tool`: a name, a description, and its parameters as JSON Schema maps. `run-tools` sends the conversation, calls your handler for each tool call, sends the results back, and returns the model's final text:

<!-- phel-test: skip -->
```phel
(def tools
  [(ai/tool "get-weather"
            "Returns current weather for a city."
            {:city {:type "string" :description "City name"}})])

(def handlers
  {"get-weather" (fn [args] (str "72F sunny in " (get args :city)))})

(ai/run-tools [{:role "user" :content "weather in Paris?"}]
              tools handlers {:max-turns 5})
; => "It's 72F and sunny in Paris."
```

`handlers` maps a tool name to a function that takes the call's input map. `run-tools` throws when a tool has no handler, or when `:max-turns` (default 5) passes without a final answer. It works with Anthropic only.

For other providers, or to control each step, run the loop yourself with `chat-with-tools`, `tool-calls`, and `tool-result`. `chat-with-tools` returns:

<!-- phel-test: skip -->
```phel
{:text        "..."   ; assistant text, nil if the model only called tools
 :tool-calls  [{:name "..." :id "..." :input {...}}]
 :stop-reason "..."
 :raw         {...}}  ; full provider response body
```

## Search by meaning

`build-index` embeds a list of texts. `search` embeds a query and returns the closest texts first. Embeddings use OpenAI by default. Pass `{:provider :voyageai}` to use Voyage AI:

<!-- phel-test: skip -->
```phel
(def index (ai/build-index ["cats purr" "dogs bark" "birds sing"]))

(ai/search "feline sounds" index {:k 1})
; => ({:text "cats purr" :embedding [...] :similarity 0.87})
```

`embed` and `embed-one` return raw embedding vectors. The vector math behind search needs no network, so you can use it in your own pipelines:

```phel
(ns my-app.embed-demo
  (:require phel.ai :as ai))

(ai/dot-product [1 2 3] [4 5 6])   ; => 32
(ai/magnitude [3 4])               ; => 5.0
(ai/cosine-similarity [1 0] [1 0]) ; => 1.0

(ai/nearest [1 0] [{:text "a" :embedding [1 0]} {:text "b" :embedding [0 1]}] 1)
; => ({:text "a", :embedding [1 0], :similarity 1.0})
```

## Configuration

`configure` merges options into the shared `ai/config` atom. Every call that takes `opts` (`chat`, `complete`, `chat-with-tools`, `extract`, `extract-many`) accepts the same keys for one call.

| Key | Default | Purpose |
|-----|---------|---------|
| `:provider` | `:anthropic` | `:anthropic`, `:openai`, or `:voyageai` |
| `:model` | `"claude-sonnet-4-6"` | model name; the default can change between releases |
| `:max-tokens` | `1024` | output token limit |
| `:api-key` | `nil` | falls back to the provider's environment variable |
| `:base-url` | `nil` | another endpoint, for a proxy or a self-hosted model |
| `:timeout` | `120` | HTTP timeout in seconds |
| `:max-retries` | `2` | retries for HTTP 429 and 5xx, with backoff of 500 ms, 1 s, 2 s, ... |

<!-- phel-test: skip -->
```phel
(ai/complete "Summarize the news" {:provider :openai :model "gpt-4o-mini" :timeout 300})

(ai/with-config {:provider :openai :model "gpt-4o"}
  (ai/complete "Summarize the news"))
```

`with-config` changes the configuration for its body only and restores it afterwards, even when the body throws.

Network errors are not retried. Every failure throws `\RuntimeException`, with the HTTP status and the provider's error body in the message when available.

## Test without a live API

`phel.ai` sends every request through the dynamic var `*http-post*`. Rebind it in a test to return a canned response. With [`phel.mock`](/documentation/reference/api/mock/) you can also count the calls:

<!-- phel-test: skip -->
```phel
(ns my-app.test.ai-test
  (:require phel.test :refer [deftest is])
  (:require phel.ai :as ai)
  (:require phel.mock :refer [mock-fn call-count]))

(deftest test-my-ai-logic
  (let [fake (mock-fn (fn [_ _] {:status 200
                                 :body "{\"content\":[{\"type\":\"text\",\"text\":\"hi\"}]}"}))]
    (binding [ai/*http-post* fake]
      (is (= "hi" (ai/complete "say hi" {:api-key "k"})))
      (is (= 1 (call-count fake))))))
```

`json/encode` writes floats as strings. When a fake response must contain embedding arrays, write the body as a raw JSON string.

Every function and its signature: [ai API reference](/documentation/reference/api/ai/). For AI coding assistants that write Phel, see [AI Agents](/documentation/tooling/ai-agents/).
