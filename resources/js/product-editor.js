import {
    BlockQuote,
    Bold,
    ClassicEditor,
    Code,
    Essentials,
    Heading,
    Italic,
    Link,
    List,
    Paragraph,
    RemoveFormat,
    Strikethrough,
    Underline,
} from 'ckeditor5';

import 'ckeditor5/ckeditor5.css';

const editorConfig = {
    licenseKey: 'GPL',
    plugins: [
        BlockQuote,
        Bold,
        Code,
        Essentials,
        Heading,
        Italic,
        Link,
        List,
        Paragraph,
        RemoveFormat,
        Strikethrough,
        Underline,
    ],
    toolbar: [
        'heading',
        '|',
        'bold',
        'italic',
        'underline',
        'strikethrough',
        'code',
        '|',
        'link',
        'bulletedList',
        'numberedList',
        'blockQuote',
        '|',
        'removeFormat',
        'undo',
        'redo',
    ],
    heading: {
        options: [
            { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
            { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
            { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
            { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' },
        ],
    },
    link: {
        addTargetToExternalLinks: true,
        defaultProtocol: 'https://',
        decorators: {
            openInNewTab: {
                mode: 'manual',
                label: 'Open in a new tab',
                attributes: {
                    target: '_blank',
                    rel: 'noopener noreferrer nofollow',
                },
            },
        },
    },
};

const syncEditorToTextarea = (editor, textarea) => {
    textarea.value = editor.getData();
};

const bootProductEditors = () => {
    document.querySelectorAll('[data-rich-text-editor]').forEach((textarea) => {
        if (textarea.dataset.editorReady === 'true') {
            return;
        }

        textarea.dataset.editorReady = 'true';

        ClassicEditor.create(textarea, editorConfig)
            .then((editor) => {
                textarea.form?.addEventListener('submit', () => syncEditorToTextarea(editor, textarea));
            })
            .catch((error) => {
                textarea.dataset.editorReady = 'false';
                console.error('Unable to initialize the product description editor.', error);
            });
    });
};

document.addEventListener('DOMContentLoaded', bootProductEditors);
