import { loadQuill } from '@vueup/vue-quill';

let configuredQuillPromise: ReturnType<typeof loadQuill> | null = null;

/**
 * Load and configure Quill once for every RichTextEditor on the page.
 * Inactive editors remain lightweight HTML previews and never call this loader.
 */
export function loadConfiguredQuill() {
    if (configuredQuillPromise) {
        return configuredQuillPromise;
    }

    configuredQuillPromise = loadQuill().then((Quill) => {
        const Font = Quill.import('attributors/style/font') as {
            whitelist: string[];
        };
        Font.whitelist = [
            'sans-serif',
            'serif',
            'monospace',
            'cursive',
            'Arial',
            'Times New Roman',
        ];
        Quill.register(Font as any, true);

        const BaseImageFormat = Quill.import('formats/image') as any;
        const imageFormatAttributes = ['alt', 'height', 'width', 'style'];

        class CustomImageFormat extends BaseImageFormat {
            static formats(domNode: HTMLElement) {
                return imageFormatAttributes.reduce<Record<string, string>>(
                    (formats, attribute) => {
                        const value = domNode.getAttribute(attribute);

                        if (value !== null) {
                            formats[attribute] = value;
                        }

                        return formats;
                    },
                    {},
                );
            }

            format(name: string, value: unknown) {
                if (imageFormatAttributes.includes(name)) {
                    if (value) {
                        this.domNode.setAttribute(name, String(value));
                    } else {
                        this.domNode.removeAttribute(name);
                    }

                    return;
                }

                super.format(name, value);
            }
        }

        Quill.register(CustomImageFormat, true);

        return Quill;
    });

    return configuredQuillPromise;
}
