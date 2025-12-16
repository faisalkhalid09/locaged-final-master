import { Modal } from 'bootstrap';

export function showPreviewModal(file) {
    const previewContent = document.getElementById('previewContent');
    previewContent.innerHTML = '';

    const type = file.type;

    if (type.startsWith('image/')) {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.style.maxWidth = '100%';
        img.onload = () => URL.revokeObjectURL(img.src);
        previewContent.appendChild(img);
    } else if (type === 'application/pdf') {
        const iframe = document.createElement('iframe');
        iframe.src = URL.createObjectURL(file);
        iframe.width = '100%';
        iframe.height = '100%';
        iframe.onload = () => URL.revokeObjectURL(iframe.src);
        previewContent.appendChild(iframe);
    } else {
        const unsupportedText = document.getElementById('previewModal')?.dataset?.unsupportedText || 'Unsupported file type';
        previewContent.innerHTML = `<p class="text-danger">${unsupportedText}</p>`;
    }

    const modal = new Modal(document.getElementById('previewModal'));
    modal.show();
}
