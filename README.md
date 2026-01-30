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

Copyright (C) Charles Chan <charles@zeerd.com>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

See the COPYING file in your DokuWiki folder for details
