<div class="modal fade" id="confirmGenericModal" tabindex="-1" aria-labelledby="confirmGenericModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header flex-column justify-content-center">
                <div class="d-flex justify-content-center mb-2" id="modalIconContainer">
                    <img id="modalIcon" src="{{ asset('assets/Frame 2078547852.svg') }}" alt="{{ ui_t('pages.messages.confirm_icon') }}" style="max-width: 80px;">
                </div>

                <h3 id="genericModalTitle" class="text-center">{{ ui_t('pages.activity_log.are_you_sure') }}</h3>
            </div>

            <div class="modal-body">

                <p id="genericModalBody" class="text-center">{{ ui_t('pages.activity_log.cannot_undo') }}</p>
                <form id="genericActionForm" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="modalMethod" value="DELETE">

                    <div id="modalExtraFields" class="mb-3"></div>


                    <div class="modal-footer">
                        <!-- Buttons container -->
                        <div>
                            <button type="button" id="cancelModalBtn" class="btn btn-secondary" data-bs-dismiss="modal">{{ ui_t('actions.cancel') }}</button>
                            <button type="submit" id="modalActionBtn" class="btn btn-danger">{{ ui_t('actions.delete') }}</button>
                        </div>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>



<script>
    // Avoid binding the generic confirm modal handler multiple times when this
    // partial is included more than once on a page.
    if (!window.__GENERIC_CONFIRM_MODAL_BOUND__) {
        window.__GENERIC_CONFIRM_MODAL_BOUND__ = true;

        const GenericModal = document.getElementById("confirmGenericModal");
        const GenericForm = document.getElementById("genericActionForm");
        const titleEl = document.getElementById("genericModalTitle");
        const bodyEl = document.getElementById("genericModalBody");
        const cancelBtn = document.getElementById("cancelModalBtn");
        const methodInput = document.getElementById("modalMethod");
        const actionBtn = document.getElementById("modalActionBtn");
        const iconImg = document.getElementById("modalIcon");
        const extraFieldsContainer = document.getElementById("modalExtraFields");

        // Use event delegation so all current and future .trigger-action buttons
        // (including those rendered later in loops) work correctly.
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.trigger-action');
            if (!btn) {
                return;
            }

            e.preventDefault();

            titleEl.textContent = btn.dataset.title || "{{ ui_t('pages.activity_log.are_you_sure') }}";
            bodyEl.textContent = btn.dataset.body || "{{ ui_t('pages.activity_log.cannot_undo') }}";
            iconImg.src = btn.dataset.icon || "{{ asset('assets/Frame 2078547852.svg') }}";
            GenericForm.action = btn.dataset.url || "#";
            methodInput.value = btn.dataset.method || "POST";
            actionBtn.textContent = btn.dataset.buttonText || "{{ ui_t('actions.submit') }}";
            actionBtn.className = `btn ${btn.dataset.buttonClass || 'btn-danger'}`;

            if (btn.dataset.extraFields) {
                try {
                    const fields = JSON.parse(btn.dataset.extraFields);
                    extraFieldsContainer.innerHTML = '';
                    fields.forEach(field => {
                        // Create label if provided
                        if (field.label) {
                            const label = document.createElement('label');
                            label.textContent = field.label;
                            label.className = 'form-label fw-semibold mb-1';
                            extraFieldsContainer.appendChild(label);
                        }

                        // Handle select dropdown
                        if (field.type === 'select') {
                            const select = document.createElement('select');
                            select.name = field.name;
                            select.className = 'form-select mb-3';
                            if (field.required) select.required = true;
                            
                            // Add options
                            if (field.options && Array.isArray(field.options)) {
                                field.options.forEach(opt => {
                                    const option = document.createElement('option');
                                    option.value = opt.value || opt;
                                    option.textContent = opt.text || opt.value || opt;
                                    if (opt.value === field.value) {
                                        option.selected = true;
                                    }
                                    select.appendChild(option);
                                });
                            }
                            
                            extraFieldsContainer.appendChild(select);
                        } else {
                            // Handle regular input fields
                            const input = document.createElement('input');
                            input.type = field.type || 'text';
                            input.name = field.name;
                            input.placeholder = field.placeholder || '';
                            input.className = 'form-control mb-3';
                            input.value = field.value || '';
                            if (field.min !== undefined) input.min = field.min;
                            if (field.max !== undefined) input.max = field.max;
                            if (field.required) input.required = true;
                            extraFieldsContainer.appendChild(input);
                        }
                    });
                } catch (err) {
                    console.error("Invalid JSON in data-extra-fields:", err);
                    extraFieldsContainer.innerHTML = '';
                }
            } else {
                extraFieldsContainer.innerHTML = '';
            }

            const bsModal = new bootstrap.Modal(GenericModal);
            bsModal.show();
        });
    }
</script>


