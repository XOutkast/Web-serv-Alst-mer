document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('clickMe');
    if(button){
        button.addEventListener('click', () => {
            alert("Button clicked! Welcome to the test website.");
        });
    }

    const form = document.getElementById('contactForm');
    if(form){
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            document.getElementById('formResponse').innerText = "Thank you, " + formData.get('name') + "! We received your message.";
            form.reset();
        });
    }
});
