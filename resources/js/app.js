import './bootstrap';
import Quill from 'quill';
import Swal from 'sweetalert2';
import 'quill/dist/quill.snow.css';
import 'sweetalert2/dist/sweetalert2.min.css';

window.Swal = Swal;

window.richTextEditor = (value, options = {}) => ({
    value,
    editor: null,

    init() {
        this.editor = new Quill(this.$refs.editor, {
            theme: 'snow',
            placeholder: options.placeholder ?? 'Write lesson content…',
            modules: {
                toolbar: [
                    [{ header: [2, 3, 4, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    [{ indent: '-1' }, { indent: '+1' }],
                    ['blockquote', 'code-block'],
                    ['link', 'image'],
                    ['clean'],
                ],
            },
        });

        this.setEditorContent(this.value);

        this.editor.on('text-change', () => {
            const content = this.editor.root.innerHTML === '<p><br></p>' ? '' : this.editor.root.innerHTML;

            if (content !== this.value) {
                this.value = content;
            }
        });
    },

    setEditorContent(content) {
        this.editor.clipboard.dangerouslyPasteHTML(content || '');
    },
});
