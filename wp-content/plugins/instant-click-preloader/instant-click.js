document.addEventListener('DOMContentLoaded', function() {
    let preloadedLinks = {};

    document.body.addEventListener('mouseover', function(e) {
        let link = e.target.closest('a');
        if (!link || link.target === '_blank' || link.href.indexOf(location.origin) !== 0) return;
        if (preloadedLinks[link.href]) return;

        let prefetch = document.createElement('link');
        prefetch.rel = 'prefetch';
        prefetch.href = link.href;
        document.head.appendChild(prefetch);

        preloadedLinks[link.href] = true;
    });
});
