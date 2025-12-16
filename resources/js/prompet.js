document.addEventListener("DOMContentLoaded", function () {
    function openModal(modalId) {
        document.getElementById(modalId)?.classList.remove("d-none");
    }
    function closeModal(modalId) {
        document.getElementById(modalId)?.classList.add("d-none");
    }

    function addOutsideClickListener(modalId, closeFn) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeFn();
        });
    }

    ["approveModal", "declineModal"].forEach(modalId => {
        addOutsideClickListener(modalId, () => closeModal(modalId));
    });

    document.querySelectorAll('.open-approve-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            const docId = btn.getAttribute('data-doc-id');
            const docTitle = btn.getAttribute('data-doc-title');
            document.getElementById('approveDocTitle').textContent = docTitle;
            document.getElementById('approveForm').action = `/documents/${docId}/approve`;
            openModal('approveModal');
        });
    });

    document.querySelectorAll('.open-decline-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            const docId = btn.getAttribute('data-doc-id');
            const docTitle = btn.getAttribute('data-doc-title');
            document.getElementById('declineDocTitle').textContent = docTitle;
            document.getElementById('declineForm').action = `/documents/${docId}/decline`;
            openModal('declineModal');
        });
    });

    document.querySelectorAll('.btn-cancel, .exit').forEach(btn => {
        btn.addEventListener('click', () => {
            closeModal('approveModal');
            closeModal('declineModal');
        });
    });
});
