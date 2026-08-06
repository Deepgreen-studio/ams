import { mergeAttributes, Node } from '@tiptap/core';

export const CustomComponent = Node.create({
  name: 'customComponent',
  group: 'block',
  atom: true,
  selectable: true,
  draggable: true,

  addAttributes() {
    return {
      component: {
        default: 'callout',
        parseHTML: (element) => element.getAttribute('data-component') || 'callout',
        renderHTML: (attributes) => ({ 'data-component': attributes.component }),
      },
      title: {
        default: 'Callout',
        parseHTML: (element) => element.getAttribute('data-title') || 'Callout',
        renderHTML: (attributes) => ({ 'data-title': attributes.title }),
      },
      body: {
        default: '',
        parseHTML: (element) => element.getAttribute('data-body') || element.textContent || '',
        renderHTML: (attributes) => ({ 'data-body': attributes.body }),
      },
    };
  },

  parseHTML() {
    return [{ tag: 'div[data-ams-component]' }];
  },

  renderHTML({ HTMLAttributes }) {
    const component = HTMLAttributes.component || HTMLAttributes['data-component'] || 'callout';
    const title = HTMLAttributes.title || HTMLAttributes['data-title'] || 'Callout';
    const body = HTMLAttributes.body || HTMLAttributes['data-body'] || '';

    return [
      'div',
      mergeAttributes(HTMLAttributes, {
        'data-ams-component': 'true',
        'data-component': component,
        'data-title': title,
        'data-body': body,
        class: 'ams-custom-component rounded-lg border border-amber-200 bg-amber-50 p-4 my-3',
      }),
      ['strong', { class: 'block text-sm font-semibold text-amber-900' }, title],
      ['p', { class: 'mt-1 text-sm text-amber-800' }, body],
    ];
  },

  addCommands() {
    return {
      insertCustomComponent:
        (attrs = {}) =>
        ({ commands }) =>
          commands.insertContent({
            type: this.name,
            attrs: {
              component: attrs.component || 'callout',
              title: attrs.title || 'Callout',
              body: attrs.body || 'Add supporting details for this custom component.',
            },
          }),
    };
  },
});
