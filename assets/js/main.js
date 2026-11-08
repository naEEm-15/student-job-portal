// Our own JavaScript goes here.
// Bootstrap's own JavaScript is loaded separately in footer.php.

// Registration page: show the company name box only when "Employer" is chosen.
document.addEventListener('DOMContentLoaded', function () {
    var companyBox = document.getElementById('company-box');
    if (!companyBox) {
        return; // not on the registration page
    }

    function updateCompanyBox() {
        var selected = document.querySelector('input[name="role"]:checked');
        companyBox.classList.toggle('d-none', !selected || selected.value !== 'employer');
    }

    document.querySelectorAll('input[name="role"]').forEach(function (radio) {
        radio.addEventListener('change', updateCompanyBox);
    });
    updateCompanyBox();
});