(function (wp, config) {
  if (!wp || !wp.plugins || !wp.editPost || !wp.element || !wp.apiFetch) {
    return;
  }

  const { registerPlugin } = wp.plugins;
  const { PluginDocumentSettingPanel } = wp.editPost;
  const { createElement: el, useEffect, useMemo, useRef, useState } = wp.element;
  const { Button, Spinner } = wp.components;
  const { useSelect } = wp.data;
  const { createBlock } = wp.blocks;
  const { serialize } = wp.blocks;
  const richText = wp.richText || {};
  const apiFetch = wp.apiFetch;
  const i18n = (config && config.i18n) || {};

  if (config && config.nonce && apiFetch.createNonceMiddleware) {
    apiFetch.use(apiFetch.createNonceMiddleware(config.nonce));
  }

  function cmaEditorGetBlockContext(clientId, attributeKey, editable) {
    const blockEditorSelect = wp.data.select('core/block-editor');
    const block = clientId ? blockEditorSelect.getBlock(clientId) : null;
    const attributeValue = block && block.attributes ? block.attributes[attributeKey || 'content'] : '';
    const html = cmaEditorGetAttributeHtml(attributeValue);
    const container = document.createElement('div');

    container.innerHTML = html;

    return ((container.textContent || (editable && editable.textContent) || '').trim()).slice(0, 3000);
  }

  function cmaEditorGetSelectionContext() {
    const selections = [];

    if (window.getSelection) {
      selections.push(window.getSelection());
    }

    const activeElement = document.activeElement;
    if (activeElement && activeElement.contentWindow && activeElement.contentWindow.getSelection) {
      selections.push(activeElement.contentWindow.getSelection());
    }

    document.querySelectorAll('iframe').forEach((iframe) => {
      try {
        if (iframe.contentWindow && iframe.contentWindow.getSelection) {
          selections.push(iframe.contentWindow.getSelection());
        }
      } catch (error) {}
    });

    for (const selection of selections) {
      const text = selection ? selection.toString().trim() : '';
      if (!text || !selection.rangeCount) continue;

      const blockEditorSelect = wp.data.select('core/block-editor');
      const selectedClientId = blockEditorSelect.getSelectedBlockClientId();
      const start = blockEditorSelect.getSelectionStart ? blockEditorSelect.getSelectionStart() : null;
      const end = blockEditorSelect.getSelectionEnd ? blockEditorSelect.getSelectionEnd() : null;

      if (
        start
        && end
        && start.clientId
        && start.clientId === end.clientId
        && Number.isInteger(start.offset)
        && Number.isInteger(end.offset)
      ) {
        const range = selection.getRangeAt(0).cloneRange();
        const container = range.commonAncestorContainer.nodeType === 1
          ? range.commonAncestorContainer
          : range.commonAncestorContainer.parentElement;

        const editable = container && container.closest ? container.closest('[contenteditable="true"]') : null;

        return {
          text,
          clientId: start.clientId,
          attributeKey: start.attributeKey || 'content',
          start: Math.min(start.offset, end.offset),
          end: Math.max(start.offset, end.offset),
          editable,
          range,
          blockContext: cmaEditorGetBlockContext(start.clientId, start.attributeKey || 'content', editable),
        };
      }

      const range = selection.getRangeAt(0);
      const container = range.commonAncestorContainer.nodeType === 1
        ? range.commonAncestorContainer
        : range.commonAncestorContainer.parentElement;
      const blockElement = container && container.closest ? container.closest('[data-block]') : null;
      const editable = container && container.closest ? container.closest('[contenteditable="true"]') : null;

      if (editable) {
        const before = range.cloneRange();
        before.selectNodeContents(editable);
        before.setEnd(range.startContainer, range.startOffset);

        return {
          text,
          clientId: (blockElement && blockElement.getAttribute('data-block')) || selectedClientId,
          attributeKey: 'content',
          start: before.toString().length,
          end: before.toString().length + range.toString().length,
          editable,
          range: range.cloneRange(),
          blockContext: cmaEditorGetBlockContext((blockElement && blockElement.getAttribute('data-block')) || selectedClientId, 'content', editable),
        };
      }

      return {
        text,
        clientId: selectedClientId,
        attributeKey: 'content',
        blockContext: cmaEditorGetBlockContext(selectedClientId, 'content', null),
      };
    }

    return { text: '', clientId: null, attributeKey: 'content', blockContext: '' };
  }

  function cmaEditorGetSelectionText() {
    return cmaEditorGetSelectionContext().text;
  }

  function cmaEditorScoreClass(score) {
    if (score >= 80) return 'is-excellent';
    if (score >= 55) return 'is-good';
    return 'is-light';
  }

  function cmaEditorPreserveSelection(event, selectionContext) {
    event.preventDefault();

    const currentSelection = cmaEditorGetSelectionContext();
    if (currentSelection.text && selectionContext) {
      Object.assign(selectionContext, currentSelection);
    }
  }

  function cmaEditorEscapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value || '';
    return div.innerHTML;
  }

  function cmaEditorGetAttributeHtml(value) {
    if (typeof value === 'string') return value;
    if (value && typeof value.toString === 'function') return value.toString();
    return '';
  }

  function cmaEditorApplyBlockSelection(url, textToLink, selectionContext, selectedClientId, attributeKey, selectedBlock, blockEditorDispatch) {
    if (
      !selectedClientId
      || !selectedBlock
      || !selectionContext
      || !Number.isInteger(selectionContext.start)
      || !Number.isInteger(selectionContext.end)
      || selectionContext.end <= selectionContext.start
      || !richText.create
      || !richText.applyFormat
      || !richText.toHTMLString
    ) {
      return false;
    }

    try {
      const content = cmaEditorGetAttributeHtml(selectedBlock.attributes[attributeKey]);
      const value = richText.create({ html: content });
      const selectedText = value.text.slice(selectionContext.start, selectionContext.end).trim();

      if (selectedText.toLocaleLowerCase() !== textToLink.toLocaleLowerCase()) return false;

      if (blockEditorDispatch.selectionChange) {
        blockEditorDispatch.selectionChange(
          selectedClientId,
          attributeKey,
          selectionContext.start,
          selectionContext.end
        );
      }

      const formatted = richText.applyFormat(value, {
        type: 'core/link',
        attributes: { url },
      }, selectionContext.start, selectionContext.end);

      blockEditorDispatch.updateBlockAttributes(selectedClientId, {
        [attributeKey]: richText.toHTMLString({ value: formatted }),
      });
      return true;
    } catch (error) {
      return false;
    }
  }

  function cmaEditorApplyHtmlRange(url, textToLink, selectionContext, selectedClientId, attributeKey, selectedBlock, blockEditorDispatch) {
    if (
      !selectedClientId
      || !selectedBlock
      || !selectionContext
      || !Number.isInteger(selectionContext.start)
      || !Number.isInteger(selectionContext.end)
      || selectionContext.end <= selectionContext.start
    ) {
      return false;
    }

    try {
      const container = document.createElement('div');
      container.innerHTML = cmaEditorGetAttributeHtml(selectedBlock.attributes[attributeKey]);

      const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT);
      let cursor = 0;
      let startNode = null;
      let startOffset = 0;
      let endNode = null;
      let endOffset = 0;
      let node = walker.nextNode();

      while (node) {
        const nextCursor = cursor + node.nodeValue.length;

        if (!startNode && selectionContext.start >= cursor && selectionContext.start <= nextCursor) {
          startNode = node;
          startOffset = selectionContext.start - cursor;
        }

        if (!endNode && selectionContext.end >= cursor && selectionContext.end <= nextCursor) {
          endNode = node;
          endOffset = selectionContext.end - cursor;
          break;
        }

        cursor = nextCursor;
        node = walker.nextNode();
      }

      if (!startNode || !endNode) return false;

      const range = document.createRange();
      range.setStart(startNode, startOffset);
      range.setEnd(endNode, endOffset);

      if (range.toString().trim().toLocaleLowerCase() !== textToLink.toLocaleLowerCase()) return false;

      const link = document.createElement('a');
      link.setAttribute('href', url);
      link.appendChild(range.extractContents());
      range.insertNode(link);

      blockEditorDispatch.updateBlockAttributes(selectedClientId, { [attributeKey]: container.innerHTML });
      return true;
    } catch (error) {
      return false;
    }
  }

  function cmaEditorApplySelectedRange(url, textToLink, selectionContext, selectedClientId, attributeKey, blockEditorDispatch) {
    if (!selectionContext.editable || !selectionContext.range) return false;

    try {
      const editable = selectionContext.editable;
      const range = selectionContext.range.cloneRange();
      const editorWindow = editable.ownerDocument.defaultView;
      const selection = editorWindow.getSelection();

      if (!editable.contains(range.commonAncestorContainer)) return false;
      if (range.toString().trim().toLocaleLowerCase() !== textToLink.toLocaleLowerCase()) return false;

      selection.removeAllRanges();
      selection.addRange(range);

      let inserted = false;
      if (editable.ownerDocument.execCommand) {
        inserted = editable.ownerDocument.execCommand('createLink', false, url);
      }

      if (!inserted) {
        const link = editable.ownerDocument.createElement('a');
        link.setAttribute('href', url);
        link.appendChild(range.extractContents());
        range.insertNode(link);
      }

      editable.dispatchEvent(new editorWindow.Event('input', { bubbles: true }));
      if (selectedClientId) {
        blockEditorDispatch.updateBlockAttributes(selectedClientId, { [attributeKey]: editable.innerHTML });
      }
      return true;
    } catch (error) {
      return false;
    }
  }

  function cmaEditorInsertLink(url, anchor, selectionContext) {
    const blockEditorSelect = wp.data.select('core/block-editor');
    const blockEditorDispatch = wp.data.dispatch('core/block-editor');
    const rememberedClientId = selectionContext && selectionContext.clientId;
    const selectedClientId = rememberedClientId || blockEditorSelect.getSelectedBlockClientId();
    const selectedBlock = selectedClientId ? blockEditorSelect.getBlock(selectedClientId) : null;
    const textToLink = ((selectionContext && selectionContext.text) || '').trim();
    const attributeKey = (selectionContext && selectionContext.attributeKey) || 'content';
    const linkLabel = textToLink || anchor || url;
    const linkHtml = '<a href="' + cmaEditorEscapeHtml(url) + '">' + cmaEditorEscapeHtml(linkLabel) + '</a>';

    if (textToLink && cmaEditorApplyBlockSelection(url, textToLink, selectionContext, selectedClientId, attributeKey, selectedBlock, blockEditorDispatch)) {
      return true;
    }

    if (textToLink && cmaEditorApplyHtmlRange(url, textToLink, selectionContext, selectedClientId, attributeKey, selectedBlock, blockEditorDispatch)) {
      return true;
    }

    if (textToLink && cmaEditorApplySelectedRange(url, textToLink, selectionContext, selectedClientId, attributeKey, blockEditorDispatch)) {
      return true;
    }

    if (selectedBlock && textToLink) {
      const content = cmaEditorGetAttributeHtml(selectedBlock.attributes[attributeKey]);

      try {
        if (
          selectionContext.editable
          && selectionContext.range
          && richText.create
          && richText.applyFormat
          && richText.toHTMLString
        ) {
          const value = richText.create({
            element: selectionContext.editable,
            range: selectionContext.range,
            __unstableIsEditableTree: true,
          });
          const selectedText = value.text.slice(value.start, value.end).trim();

          if (selectedText.toLocaleLowerCase() === textToLink.toLocaleLowerCase()) {
            const formatted = richText.applyFormat(value, {
              type: 'core/link',
              attributes: { url },
            });

            blockEditorDispatch.updateBlockAttributes(selectedClientId, {
              [attributeKey]: richText.toHTMLString({ value: formatted }),
            });
            return true;
          }
        }
      } catch (error) {}

      try {
        if (
          Number.isInteger(selectionContext.start)
          && Number.isInteger(selectionContext.end)
          && selectionContext.end > selectionContext.start
          && richText.create
          && richText.applyFormat
          && richText.toHTMLString
        ) {
          const value = richText.create({ html: content });
          const selectedText = value.text.slice(selectionContext.start, selectionContext.end).trim();

          if (selectedText.toLocaleLowerCase() === textToLink.toLocaleLowerCase()) {
            const formatted = richText.applyFormat(value, {
              type: 'core/link',
              attributes: { url },
            }, selectionContext.start, selectionContext.end);

            blockEditorDispatch.updateBlockAttributes(selectedClientId, {
              [attributeKey]: richText.toHTMLString({ value: formatted }),
            });
            return true;
          }
        }
      } catch (error) {}

      const hasRememberedOffsets = Number.isInteger(selectionContext.start) && Number.isInteger(selectionContext.end);
      const index = hasRememberedOffsets ? -1 : content.toLocaleLowerCase().indexOf(textToLink.toLocaleLowerCase());

      if (index !== -1) {
        const updatedContent = content.slice(0, index) + linkHtml + content.slice(index + textToLink.length);
        blockEditorDispatch.updateBlockAttributes(selectedClientId, { [attributeKey]: updatedContent });
        return true;
      }

      return false;
    }

    if (textToLink) {
      return false;
    }

    const newBlock = createBlock('core/paragraph', { content: linkHtml });
    const selectedIndex = selectedClientId ? blockEditorSelect.getBlockIndex(selectedClientId) : -1;
    const rootClientId = selectedClientId ? blockEditorSelect.getBlockRootClientId(selectedClientId) : undefined;

    if (selectedIndex >= 0) {
      blockEditorDispatch.insertBlocks(newBlock, selectedIndex + 1, rootClientId);
    } else {
      blockEditorDispatch.insertBlocks(newBlock);
    }

    return true;
  }

  function CmaSuggestionCard({ suggestion, selectionContext }) {
    const firstAnchor = suggestion.anchors && suggestion.anchors.length ? suggestion.anchors[0] : suggestion.title;
    const [inserted, setInserted] = useState(false);
    const [insertError, setInsertError] = useState(false);

    function insertLink(anchor) {
      const success = cmaEditorInsertLink(suggestion.url, anchor, selectionContext);
      if (success) {
        setInsertError(false);
        setInserted(true);
        setTimeout(() => setInserted(false), 2200);
      } else {
        setInsertError(true);
        setTimeout(() => setInsertError(false), 3500);
      }
    }

    return el('div', { className: 'cma-suggestion-card' },
      el('div', { className: 'cma-suggestion-card-head' },
        el('strong', null, suggestion.title),
        el('span', { className: 'cma-suggestion-score ' + cmaEditorScoreClass(suggestion.score) }, suggestion.score)
      ),
      el('a', { className: 'cma-suggestion-url', href: suggestion.url, target: '_blank', rel: 'noopener noreferrer' }, suggestion.url),
      el('div', { className: 'cma-suggestion-relations' },
        (suggestion.relations || []).map((relation) => el('span', { key: relation }, relation))
      ),
      el('p', { className: 'cma-suggestion-reason' }, suggestion.reason),
      el('div', { className: 'cma-suggestion-anchors' },
        el('span', { className: 'cma-suggestion-label' }, i18n.suggestedAnchors || 'Suggested anchors'),
        (suggestion.anchors || []).map((anchor) => el('button', {
          key: anchor,
          type: 'button',
          className: 'cma-suggestion-anchor',
          onMouseDown: (event) => cmaEditorPreserveSelection(event, selectionContext),
          onClick: () => insertLink(anchor)
        }, anchor))
      ),
      el('div', { className: 'cma-suggestion-actions' },
        el(Button, {
          variant: 'primary',
          onMouseDown: (event) => cmaEditorPreserveSelection(event, selectionContext),
          onClick: () => insertLink(firstAnchor)
        }, inserted ? (i18n.linkInserted || 'Link inserted') : (i18n.insertLink || 'Insert link'))
      ),
      insertError ? el('p', { className: 'cma-suggestion-insert-error' }, i18n.reselectText || 'Select the text again in the content and retry.') : null
    );
  }

  function CmaOpportunityCard({ opportunity, selectionContext }) {
    return el('div', { className: 'cma-opportunity-card' },
      el('div', null,
        el('span', { className: 'cma-suggestion-label' }, i18n.detectedExpression || 'Detected expression'),
        el('strong', null, opportunity.expression)
      ),
      el('p', null, opportunity.title),
      el(Button, {
        variant: 'secondary',
        onMouseDown: (event) => cmaEditorPreserveSelection(event, selectionContext),
        onClick: () => cmaEditorInsertLink(opportunity.url, opportunity.anchor, selectionContext)
      }, i18n.addLink || 'Add link')
    );
  }

  function CmaGutenbergSuggestionsPanel() {
    const initialSelection = cmaEditorGetSelectionContext();
    const [selectionText, setSelectionText] = useState(initialSelection.text);
    const [blockContext, setBlockContext] = useState(initialSelection.blockContext || '');
    const [state, setState] = useState({ loading: false, data: null, error: '' });
    const selectionRef = useRef(initialSelection);

    const editorContext = useSelect((select) => {
      const editor = select('core/editor');
      const blockEditor = select('core/block-editor');
      const blocks = blockEditor.getBlocks();

      return {
        postId: editor.getCurrentPostId(),
        title: editor.getEditedPostAttribute('title') || '',
        slug: editor.getEditedPostAttribute('slug') || '',
        content: serialize(blocks),
      };
    }, []);

    useEffect(() => {
      let timer = null;
      let lastSelection = selectionText;
      let lastBlockContext = blockContext;

      function handleSelectionChange() {
        clearTimeout(timer);
        timer = setTimeout(() => {
          const nextSelectionContext = cmaEditorGetSelectionContext();
          const nextSelection = nextSelectionContext.text;
          const nextBlockContext = nextSelectionContext.blockContext || '';
          if (nextSelection !== lastSelection || nextBlockContext !== lastBlockContext) {
            lastSelection = nextSelection;
            lastBlockContext = nextBlockContext;
            if (nextSelection) {
              selectionRef.current = nextSelectionContext;
            }
            setSelectionText(nextSelection);
            setBlockContext(nextBlockContext);
          }
        }, 250);
      }

      document.addEventListener('selectionchange', handleSelectionChange);
      document.addEventListener('mouseup', handleSelectionChange);
      document.addEventListener('keyup', handleSelectionChange);

      const pollSelection = setInterval(handleSelectionChange, 700);

      return () => {
        clearTimeout(timer);
        clearInterval(pollSelection);
        document.removeEventListener('selectionchange', handleSelectionChange);
        document.removeEventListener('mouseup', handleSelectionChange);
        document.removeEventListener('keyup', handleSelectionChange);
      };
    }, []);

    const requestBody = useMemo(() => ({
      postId: editorContext.postId,
      title: editorContext.title,
      slug: editorContext.slug,
      content: editorContext.content,
      selection: selectionText,
      blockContext,
    }), [editorContext.postId, editorContext.title, editorContext.slug, editorContext.content, selectionText, blockContext]);

    useEffect(() => {
      const contentLength = (requestBody.content || '').replace(/<[^>]*>/g, '').trim().length;

      if (!requestBody.postId || (contentLength < 20 && !requestBody.selection)) {
        setState({ loading: false, data: null, error: '' });
        return undefined;
      }

      const timer = setTimeout(() => {
        setState((prev) => ({ ...prev, loading: true, error: '' }));

        apiFetch({
          path: (config && config.restPath) || '/cma/v1/link-suggestions',
          method: 'POST',
          data: requestBody,
        }).then((data) => {
          setState({ loading: false, data, error: '' });
        }).catch((error) => {
          setState({ loading: false, data: null, error: error.message || (i18n.loadingError || 'Loading error.') });
        });
      }, 900);

      return () => clearTimeout(timer);
    }, [requestBody]);

    const data = state.data || {};
    const suggestions = data.suggestions || [];
    const opportunities = data.opportunities || [];

    return el(PluginDocumentSettingPanel, {
      name: 'cma-gutenberg-suggestions',
      title: i18n.suggestionsPanel || 'Internal linking suggestions',
      className: 'cma-gutenberg-suggestions',
    },
      el('p', { className: 'cma-gutenberg-help' }, i18n.recommendationsHelp || 'Recommended content to strengthen your internal linking.'),
      selectionText ? el('div', { className: 'cma-selection-context' },
        el('span', null, i18n.analyzedSelection || 'Analyzed selection'),
        el('strong', null, selectionText)
      ) : null,
      state.loading ? el('div', { className: 'cma-suggestion-loading' }, el(Spinner), el('span', null, i18n.loading || 'Analyzing...')) : null,
      state.error ? el('p', { className: 'cma-suggestion-error' }, state.error) : null,
      data.hasScanData === false ? el('p', { className: 'cma-suggestion-empty' }, i18n.runScanNotice || 'Run a dashboard scan to enrich suggestions.') : null,
      el('div', { className: 'cma-suggestion-count' },
        el('span', null, i18n.suggestionsFound || 'Suggestions found'),
        el('strong', null, String(data.count || 0))
      ),
      suggestions.length
        ? suggestions.map((suggestion) => el(CmaSuggestionCard, { key: suggestion.id, suggestion, selectionContext: selectionRef.current }))
        : (!state.loading ? el('p', { className: 'cma-suggestion-empty' }, i18n.noSuggestion || 'No relevant suggestions at this time.') : null),
      !selectionText && opportunities.length ? el('div', { className: 'cma-opportunities' },
        el('h4', null, i18n.detectedOpportunities || 'Detected opportunities'),
        opportunities.map((opportunity) => el(CmaOpportunityCard, { key: opportunity.expression + opportunity.url, opportunity, selectionContext: selectionRef.current }))
      ) : null
    );
  }

  registerPlugin('cma-gutenberg-link-suggestions', {
    render: CmaGutenbergSuggestionsPanel,
    icon: 'admin-links',
  });
})(window.wp, window.CMAGutenbergSuggestions || {});
