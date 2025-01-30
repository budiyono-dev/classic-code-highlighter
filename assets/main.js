class CopyButtonPlugin {
    "after:highlightElement"({el, text}) {
        if (el.parentElement.parentElement.querySelector('.cch-header').querySelector(".hljs-copy-button")) return;
        let button = Object.assign(document.createElement("button"), {
            innerHTML: "Copy",
            className: "hljs-copy-button",
        });
        button.dataset.copied = false;
        const cchHeader = el.parentElement.parentElement.querySelector('.cch-header');
        cchHeader.appendChild(button);

        const nonNavigator = (text) => {
            const textArea = document.createElement("textarea");
            textArea.value = text;

            textArea.style.position = "absolute";
            textArea.style.left = "-999999px";

            document.body.prepend(textArea);
            textArea.select();

            try {
                document.execCommand('copy');
            } catch (error) {
                console.error(error);
            } finally {
                textArea.remove();
                updateDataCopy();
            }
        }
        const copyNavigator = (text) => navigator.clipboard.writeText(text).then(updateDataCopy());

        const updateDataCopy = () => {
            if (button.dataset.copied === 'true') return;

            button.innerHTML = "Copied!";
            button.dataset.copied = true;

            let alert = Object.assign(document.createElement("div"), {
                role: "status",
                className: "hljs-copy-alert",
                innerHTML: "Copied to clipboard",
            });
            cchHeader.querySelector('span').after(alert);

            setTimeout(() => {
                button.innerHTML = "Copy";
                button.dataset.copied = false;
                alert.remove();
                alert = null;
            }, 2000);
        }

        button.onclick = function () {
            if (!navigator.clipboard) {
                nonNavigator(text);
                return;
            }

            copyNavigator(text);
        };
    }
}

jQuery(document).ready(function ($) {
    $('.cch-manual').each(function (idx, e) {
        const filename = e?.dataset?.filename ?? '';
        if (e.tagName === 'CODE') {
            const cchElement = '<div class="cch-container"><div class="cch-header"><span class="filename">' +
                filename + '</span></div><pre>' + $(this).closest('pre').html() + '</pre></div>';
            $(this).closest('pre').replaceWith(cchElement);
        } else if (e.tagName === 'PRE') {
            const listClass = Array.from(e.classList);
            let filename = '';
            let language = '';
            listClass.forEach(cls => {
                if (cls.includes('cch-name')) {
                    const clsS = cls.split('-');
                    if (clsS.length < 3) return;
                    for (let i = 2; i < clsS.length - 1; i++) {
                        filename += clsS[i];
                    }
                    filename += '.' + clsS[clsS.length - 1];
                } else if (cls.includes('cch-code')) {
                    const clsS = cls.split('-');
                    if (clsS.length < 3) return;
                    language = 'language-' + clsS[2];
                }
            });
            const cchElement = '<div class="cch-container"><div class="cch-header"><span class="filename">' +
                filename + '</span></div><pre><code class="cch-manual ' + language + '">' + $(this).find('code').html() + '</code></pre></div>';
            $(this).replaceWith(cchElement);
        }

    });
    hljs.addPlugin(new CopyButtonPlugin());

    $('pre code').each(function (idx, el) {
        const classlist = el.classList;
        if (classlist.contains('cch_sc') || classlist.contains("cch-manual")) {
            hljs.highlightElement(el);
        }
    });
});
