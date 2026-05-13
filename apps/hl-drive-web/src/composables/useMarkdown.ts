import MarkdownIt from 'markdown-it';
import TurndownService from 'turndown';

let md: MarkdownIt | null = null;
let turndown: TurndownService | null = null;

export function useMarkdown() {
    if (!md) {
        md = new MarkdownIt({
            html: true,
            linkify: true,
            typographer: true,
        });
    }

    if (!turndown) {
        turndown = new TurndownService();
    }

    function renderMarkdown(content: string | null | undefined): string {
        content = String(content || '').trim();

        if (!content) {
            return '';
        }

        if (!md) {
            return content;
        }

        return md.render(content);
    }

    function turndownIt(content: string | null | undefined): string {
        return turndown.turndown(content || '');
    }

    return {
        renderMarkdown,
        turndownIt,
        turndown: turndownIt,
        toMarkdown: turndownIt,
        toHtml: renderMarkdown,
    };
}
