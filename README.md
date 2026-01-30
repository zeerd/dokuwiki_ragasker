## RagAsker Plugin for DokuWiki

RagAsker is a DokuWiki plugin that brings Retrieval-Augmented Generation (RAG) powered Q&A capabilities to your wiki. It integrates with OpenAI or compatible APIs to provide intelligent, context-aware answers based on your wiki content.

### Main Features

- **AI-Powered Q&A Widget**: Adds an interactive widget to your wiki pages, allowing users to input questions and receive AI-generated answers.
- **Keyword Extraction & Search**: Automatically extracts keywords from user queries and searches relevant wiki pages to enhance answer accuracy.
- **Retrieval-Augmented Generation**: Combines retrieved wiki content with large language models to generate context-rich responses.
- **Custom Syntax Support**: Use `~~RAGASKER~~` syntax to embed the Q&A widget anywhere in your wiki.
- **Multi-language UI**: Supports multiple languages for user interface and prompts.
- **Configurable**: Easily set API endpoint, key, model, and other parameters via plugin settings.

### Typical Use Cases

- Knowledge base Q&A
- Wiki content search and summarization
- Intelligent assistant for documentation

### Requirements

- DokuWiki installation
- Access to OpenAI API or compatible LLM service

### Installation

1. Copy the plugin folder to `lib/plugins/ragasker` in your DokuWiki installation.
2. Configure API settings in the plugin configuration page.
3. Add `~~RAGASKER~~` to any wiki page to enable the widget.

### License

See plugin.info.txt for details.
