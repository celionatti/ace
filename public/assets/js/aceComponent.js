class LiveComponent
{
    constructor(componentId, initialData, elementId) {
        this.componentId = componentId;
        this.data = initialData;
        this.element = document.getElementById(elementId);
        this.setupListeners();
    }

    setupListeners() {
        // Find all interactive elements within this component
        this.element.querySelectorAll('[wire:model]').forEach(el => {
            const property = el.getAttribute('wire:model');

            // Set initial value from data
            if (this.data[property] !== undefined) {
                if (el.type === 'checkbox') {
                    el.checked = !!this.data[property];
                } else {
                    el.value = this.data[property];
                }
            }

            // Handle change events
            el.addEventListener('input', () => {
                const newValue = el.type === 'checkbox' ? el.checked : el.value;
                this.updateProperty(property, newValue);
            });
        });

        // Handle click events for wire:click
        this.element.querySelectorAll('[wire:click]').forEach(el => {
            const method = el.getAttribute('wire:click');
            el.addEventListener('click', (e) => {
                e.preventDefault();
                this.callMethod(method);
            });
        });
    }

    updateProperty(property, value) {
        this.data[property] = value;

        // Send update to server
        this.sendRequest({
            type: 'syncProperty',
            property: property,
            value: value
        });
    }

    callMethod(method, params = {}) {
        this.sendRequest({
            type: 'callMethod',
            method: method,
            params: params
        });
    }

    sendRequest(payload) {
        // Show loading state if needed
        this.showLoading();

        fetch('/livewire/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                componentId: this.componentId,
                ...payload
            })
        })
        .then(response => response.json())
        .then(response => {
            if (response.html) {
                // Update the DOM with new HTML
                this.updateDom(response.html);
            }

            if (response.data) {
                // Update local data
                this.data = { ...this.data, ...response.data };
            }

            // Handle any events or redirects
            if (response.events) {
                this.handleEvents(response.events);
            }

            this.hideLoading();
        })
        .catch(error => {
            console.error('LiveComponent request failed:', error);
            this.hideLoading();
        });
    }

    updateDom(html) {
        // Use a morphdom or similar library to efficiently update the DOM
        // This preserves focus and reduces flickering
        morphdom(this.element, html, {
            onBeforeElUpdated: (fromEl, toEl) => {
                // Preserve some element properties that should not be overwritten
                if (fromEl.isEqualNode(toEl)) {
                    return false;
                }

                // Don't update if element is focused
                if (fromEl === document.activeElement && fromEl.tagName === 'INPUT') {
                    const newValue = toEl.value;
                    fromEl.value = newValue;
                    return false;
                }

                return true;
            }
        });

        // Re-attach event listeners
        this.setupListeners();
    }

    showLoading() {
        // Add loading indicators as needed
        this.element.querySelectorAll('[wire:loading]').forEach(el => {
            el.style.display = 'block';
        });
    }

    hideLoading() {
        this.element.querySelectorAll('[wire:loading]').forEach(el => {
            el.style.display = 'none';
        });
    }

    handleEvents(events) {
        events.forEach(event => {
            // Dispatch event to document
            document.dispatchEvent(new CustomEvent(event.name, {
                detail: event.data
            }));
        });
    }
}

// AlpineJS Integration
function initializeAlpineIntegration()
{
    if (typeof Alpine !== 'undefined') {
        document.addEventListener('DOMContentLoaded', () => {
            // Find all Alpine components that should be connected to Livewire
            document.querySelectorAll('[x-data][wire:id]').forEach(el => {
                const componentId = el.getAttribute('wire:id');
                const initialData = JSON.parse(el.getAttribute('wire:initial-data') || '{}');

                // Create and store the LiveComponent instance
                const liveComponent = new LiveComponent(componentId, initialData, el.id);

                // Make component methods available to Alpine
                Alpine.addMagicProperty('wire', el => {
                    return {
                        get: (property) => liveComponent.data[property],
                        set: (property, value) => liveComponent.updateProperty(property, value),
                        call: (method, ...params) => liveComponent.callMethod(method, params)
                    };
                });
            });
        });
    }
}

// Initialize the framework
document.addEventListener('DOMContentLoaded', () => {
    // Initialize all LiveComponents
    document.querySelectorAll('[wire:id]').forEach(el => {
        if (el.id) {
            const componentId = el.getAttribute('wire:id');
            const initialData = JSON.parse(el.getAttribute('wire:initial-data') || '{}');
            new LiveComponent(componentId, initialData, el.id);
        }
    });

    // Initialize Alpine integration if available
    initializeAlpineIntegration();
});