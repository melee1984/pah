document.addEventListener('DOMContentLoaded', function () {
    const previews = [];

    document.querySelectorAll('input[type="file"][data-file-preview]').forEach(function (input) {
        const preview = input.nextElementSibling;
        if (!preview || !preview.classList.contains('restaurant-file-preview')) return;

        let objectUrl = null;
        previews.push(function () {
            if (objectUrl) URL.revokeObjectURL(objectUrl);
        });

        input.addEventListener('change', function () {
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = null;
            preview.replaceChildren();
            preview.classList.remove('is-visible');

            const file = input.files && input.files[0];
            if (!file) return;

            const title = document.createElement('strong');
            title.textContent = file.name;
            preview.appendChild(title);

            objectUrl = URL.createObjectURL(file);
            if (file.type.startsWith('image/')) {
                const image = document.createElement('img');
                image.src = objectUrl;
                image.alt = 'Preview of ' + file.name;
                preview.appendChild(image);
            } else if (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')) {
                const frame = document.createElement('iframe');
                frame.src = objectUrl;
                frame.title = 'Preview of ' + file.name;
                preview.appendChild(frame);
            }

            const size = document.createElement('small');
            size.textContent = 'Selected file · ' + (file.size / 1024 / 1024).toFixed(2) + ' MB · Preview before submission';
            preview.appendChild(size);
            preview.classList.add('is-visible');
        });
    });

    window.addEventListener('pagehide', function () { previews.forEach(function (revoke) { revoke(); }); });
});
