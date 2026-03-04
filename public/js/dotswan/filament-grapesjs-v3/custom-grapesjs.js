// Custom GrapesJS JavaScript
console.log('Custom GrapesJS JS loaded');

// Wait for GrapesJS to be initialized
document.addEventListener('DOMContentLoaded', function() {
    // Function to enhance GrapesJS editor
    window.enhanceGrapesJSEditor = function(editor) {
        console.log('Enhancing GrapesJS editor with custom functionality');
        
        // Add custom commands
        editor.Commands.add('save-template', {
            run: function(editor) {
                const html = editor.getHtml();
                const css = editor.getCss();
                console.log('Template saved:', { html, css });
                
                // You can add your save logic here
                alert('Template saved successfully!');
            }
        });
        
        // Add custom blocks
        const blockManager = editor.BlockManager;
        
        // Custom PUIUX Button Block
        blockManager.add('puiux-button', {
            label: 'PUIUX Button',
            category: 'PUIUX Components',
            content: '<button class="btn-puiux">PUIUX Button</button>',
            media: '<i class="fa fa-square"></i>',
            attributes: { class: 'fa fa-square' }
        });
        
        // Custom PUIUX Card Block
        blockManager.add('puiux-card', {
            label: 'PUIUX Card',
            category: 'PUIUX Components',
            content: `
                <div class="puiux-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 10px; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h3>Card Title</h3>
                    <p>Card content goes here...</p>
                    <button class="btn-puiux">Action Button</button>
                </div>
            `,
            media: '<i class="fa fa-square-o"></i>'
        });
        
        // Add custom component types
        editor.DomComponents.addType('puiux-button', {
            model: {
                defaults: {
                    tagName: 'button',
                    classes: ['btn-puiux'],
                    content: 'PUIUX Button',
                    traits: [
                        {
                            type: 'text',
                            label: 'Button Text',
                            name: 'content'
                        },
                        {
                            type: 'select',
                            label: 'Button Style',
                            name: 'class',
                            options: [
                                { value: 'btn-puiux', name: 'Primary' },
                                { value: 'btn-puiux-outline', name: 'Outline' },
                                { value: 'btn-puiux btn-puiux-large', name: 'Large' },
                                { value: 'btn-puiux btn-puiux-small', name: 'Small' }
                            ]
                        }
                    ]
                }
            }
        });
        
        // Add panel buttons
        const panels = editor.Panels;
        
        panels.addButton('options', {
            id: 'save-template',
            className: 'fa fa-save',
            command: 'save-template',
            attributes: { title: 'Save Template' }
        });
        
        // Add device manager for responsive design
        const deviceManager = editor.DeviceManager;
        deviceManager.add('Desktop', '100%');
        deviceManager.add('Tablet', '768px');
        deviceManager.add('Mobile', '320px');
        
        // Add event listeners
        editor.on('load', function() {
            console.log('GrapesJS editor loaded');
        });
        
        editor.on('update', function() {
            console.log('Editor content updated');
        });
        
        // Custom CSS injection for better styling
        const css = `
            .puiux-card {
                transition: all 0.3s ease;
            }
            .puiux-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
            }
        `;
        
        // Add CSS to the editor's canvas
        editor.addCss(css);
    };
    
    // Auto-enhance editors when they're created
    window.addEventListener('grapesjs:loaded', function(e) {
        if (e.detail && e.detail.editor) {
            enhanceGrapesJSEditor(e.detail.editor);
        }
    });
});

// Utility functions
window.puiuxUtils = {
    // Function to export template
    exportTemplate: function(editor) {
        const html = editor.getHtml();
        const css = editor.getCss();
        
        const fullTemplate = `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PUIUX Template</title>
    <style>${css}</style>
</head>
<body>
    ${html}
</body>
</html>`;
        
        // Create download link
        const blob = new Blob([fullTemplate], { type: 'text/html' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'puiux-template.html';
        a.click();
        URL.revokeObjectURL(url);
    },
    
    // Function to import template
    importTemplate: function(editor, htmlContent) {
        editor.setComponents(htmlContent);
        console.log('Template imported successfully');
    }
}; 