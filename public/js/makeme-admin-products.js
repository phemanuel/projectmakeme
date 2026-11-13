
/**
 * Project MakeMe Live
 * Products & Stock Administration
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    let currentModal = null;
    let previousFocus = null;

    const createModal = document.getElementById('pmProductModal');
    const productForm = document.getElementById('pmProductForm');
    const stockForm = document.getElementById('pmStockForm');
    const archiveForm = document.getElementById('pmArchiveForm');

    const createUrl = productForm ? productForm.action : null;

    function openModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        previousFocus = document.activeElement;

        if (currentModal) currentModal.hidden = true;

        currentModal = modal;
        modal.hidden = false;
        document.body.classList.add('pm-modal-open');

        modal.querySelector(
            'input:not([type="hidden"]), select, textarea, button'
        )?.focus();
    }

    function closeModal() {
        if (!currentModal) return;

        currentModal.hidden = true;
        currentModal = null;
        document.body.classList.remove('pm-modal-open');

        if (previousFocus?.isConnected) previousFocus.focus();
        previousFocus = null;
    }

    document.querySelectorAll('[data-pm-close]').forEach(button => {
        button.addEventListener('click', closeModal);
    });

    document.querySelectorAll('.pm-overlay').forEach(modal => {
        modal.addEventListener('click', event => {
            if (event.target === modal) closeModal();
        });
    });

    document.addEventListener('keydown', event => {
        if (!currentModal) return;

        if (event.key === 'Escape') {
            event.preventDefault();
            closeModal();
            return;
        }

        if (event.key !== 'Tab') return;

        const elements = Array.from(
            currentModal.querySelectorAll(
                'button:not([disabled]), ' +
                'input:not([disabled]):not([type="hidden"]), ' +
                'select:not([disabled]), textarea:not([disabled]), ' +
                'a[href]'
            )
        ).filter(el => el.getClientRects().length > 0);

        if (!elements.length) return;

        const first = elements[0];
        const last = elements[elements.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    function productData(id) {
        const element = document.getElementById('pmProduct' + id);

        if (!element) return null;

        try {
            return JSON.parse(element.textContent);
        } catch (error) {
            console.error('Invalid product data', error);
            return null;
        }
    }

    function resetSubmission(form) {
        if (!form) return;

        form.dataset.submitting = 'false';

        const button = form.querySelector('[type="submit"]');

        if (button) {
            button.disabled = false;
            button.textContent = button.dataset.originalText ||
                button.textContent;
        }
    }

    // Create / Edit Product
    function setProductMode(product = null) {
        if (!productForm) return;

        productForm.reset();
        resetSubmission(productForm);

        const editing = !!product;

        document.getElementById('pmProductModalTitle').textContent =
            editing ? 'Edit Product' : 'Add Product';

        document.getElementById('pmProductSave').textContent =
            editing ? 'Save Changes' : 'Create Product';

        document.getElementById('pmProductMethod').value =
            editing ? 'PUT' : 'POST';

        document.getElementById('pmProductId').value =
            product?.id || '';

        productForm.action = editing
            ? product.update_url
            : createUrl;

        document.getElementById('pmName').value =
            product?.name || '';

        document.getElementById('pmSku').value =
            product?.sku || '';

        document.getElementById('pmCategory').value =
            product?.category || 'general';

        document.getElementById('pmPrice').value =
            product?.credit_price ?? '';

        document.getElementById('pmMax').value =
            product?.max_per_participant ?? '';

        document.getElementById('pmDescription').value =
            product?.description || '';

        document.getElementById('pmActive').checked =
            editing ? !!product.is_active : true;

        const initialField = document.getElementById(
            'pmInitialStockField'
        );

        const initialInput = document.getElementById(
            'pmInitialStock'
        );

        initialField.hidden = editing;
        initialInput.disabled = editing;
        initialInput.required = !editing;
        initialInput.value = editing ? '' : '0';

        const preview = document.getElementById('pmImagePreview');
        preview.hidden = !product?.image_url;
        preview.removeAttribute('src');

        if (product?.image_url) {
            preview.src = product.image_url;
        }

        openModal('pmProductModal');
    }

    document.querySelectorAll('[data-pm-open]').forEach(button => {
        button.addEventListener('click', () => {
            if (button.dataset.pmOpen === 'pmCreateModal') {
                setProductMode();
            }
        });
    });

    document.querySelectorAll('[data-pm-edit]').forEach(button => {
        button.addEventListener('click', () => {
            const product = productData(button.dataset.pmEdit);

            if (product) setProductMode(product);
        });
    });

    // Image preview
    const imageInput = document.getElementById('pmImage');

    imageInput?.addEventListener('change', function () {
        const file = this.files?.[0];
        const preview = document.getElementById('pmImagePreview');

        if (!preview) return;

        if (!file) {
            preview.hidden = true;
            preview.removeAttribute('src');
            return;
        }

        if (
            !['image/jpeg', 'image/png', 'image/webp'].includes(file.type) ||
            file.size > 3 * 1024 * 1024
        ) {
            this.value = '';
            window.alert('Choose a JPEG, PNG or WEBP image under 3 MB.');
            return;
        }

        const reader = new FileReader();

        reader.onload = () => {
            preview.src = reader.result;
            preview.hidden = false;
        };

        reader.readAsDataURL(file);
    });

    // Stock Adjustment
    function setStockMode(product) {
        if (!stockForm || !product) return;

        stockForm.reset();
        resetSubmission(stockForm);

        stockForm.action = product.stock_url;

        document.getElementById('pmStockProductId').value =
            product.id;

        document.getElementById('pmStockSubtitle').textContent =
            product.name + ' — Current Stock: ' +
            product.stock_quantity;

        document.getElementById('pmStockType').value = 'add';
        document.getElementById('pmStockQuantity').value = '';
        document.getElementById('pmStockReason').value = '';

        openModal('pmStockModal');
    }

    document.querySelectorAll('[data-pm-stock]').forEach(button => {
        button.addEventListener('click', () => {
            const product = productData(button.dataset.pmStock);

            if (product) setStockMode(product);
        });
    });

    // Archive Product
    document.querySelectorAll('[data-pm-archive]').forEach(button => {
        button.addEventListener('click', () => {
            const product = productData(button.dataset.pmArchive);

            if (!product || !archiveForm) return;

            archiveForm.action = product.archive_url;
            resetSubmission(archiveForm);

            document.getElementById('pmArchiveMessage').textContent =
                'Archive "' + product.name +
                '"? The product will become inactive but its records will remain.';

            openModal('pmArchiveModal');
        });
    });

    // Prevent duplicate submissions
    document.querySelectorAll(
        '#pmProductForm, #pmStockForm, #pmArchiveForm'
    ).forEach(form => {
        const button = form.querySelector('[type="submit"]');

        if (button) {
            button.dataset.originalText = button.textContent.trim();
        }

        form.addEventListener('submit', event => {
            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            if (!form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
                return;
            }

            form.dataset.submitting = 'true';

            if (button) {
                button.disabled = true;
                button.textContent = 'Saving...';
            }
        });
    });

    // Restore submitted form after Laravel validation errors
    const validation = document.getElementById('pmValidation');

    if (validation) {
        let values = {};

        try {
            values = JSON.parse(validation.dataset.values || '{}');
        } catch (error) {
            console.error('Could not restore validation values.', error);
        }

        const id = validation.dataset.productId;
        const product = id ? productData(id) : null;

        if (validation.dataset.modal === 'product') {
            if (id && !product) return;

            setProductMode(product);

            [
                ['name', 'pmName'],
                ['sku', 'pmSku'],
                ['category', 'pmCategory'],
                ['credit_price', 'pmPrice'],
                ['max_per_participant', 'pmMax'],
                ['description', 'pmDescription'],
                ['stock_quantity', 'pmInitialStock'],
            ].forEach(([key, elementId]) => {
                if (Object.prototype.hasOwnProperty.call(values, key)) {
                    const element = document.getElementById(elementId);
                    if (element) element.value = values[key] ?? '';
                }
            });

            document.getElementById('pmActive').checked =
                String(values.is_active ?? '0') === '1';
        }

        if (validation.dataset.modal === 'stock' && product) {
            setStockMode(product);

            document.getElementById('pmStockType').value =
                values.type || 'add';

            document.getElementById('pmStockQuantity').value =
                values.quantity ?? '';

            document.getElementById('pmStockReason').value =
                values.reason || '';
        }
    }
});
