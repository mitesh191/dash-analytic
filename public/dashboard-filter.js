document.addEventListener('DOMContentLoaded', function () {
    ['s-st', 's-lm'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', function () {
                document.getElementById('ff').submit();
            });
        }
    });
});
