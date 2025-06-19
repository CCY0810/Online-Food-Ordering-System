// Existing functionality remains the same
const editButton = document.getElementById('editButton');
const valueTexts = document.querySelectorAll('.value-text');
const editableFields = document.querySelectorAll('.editable-field');

editButton.addEventListener('click', function() {
    if (this.textContent === 'Edit Profile') {
        // Switch to edit mode
        this.textContent = 'Save Changes';
        this.classList.add('submit');
        
        valueTexts.forEach(text => text.style.display = 'none');
        editableFields.forEach(field => {
            field.style.display = 'block';
        });
    } else {
        // Switch back to view mode
        this.textContent = 'Edit Profile';
        this.classList.remove('submit');
        
        // Update the displayed values
        editableFields.forEach((field, index) => {
            if (field.type === 'password' && field.value !== 'password123') {
                valueTexts[index].textContent = '••••••••';
            } else {
                valueTexts[index].textContent = field.value;
            }
        });
        
        valueTexts.forEach(text => text.style.display = '');
        editableFields.forEach(field => field.style.display = 'none');
        
        // Update the name in the photo section if full name was changed
        document.getElementById('userNameDisplay').textContent = 
            document.getElementById('fullNameField').value;
        
        // Data handling would go here
        const updatedData = {
            fullName: editableFields[0].value,
            username: editableFields[1].value,
            password: editableFields[2].value,
            email: editableFields[3].value,
            age: editableFields[4].value,
            phone: editableFields[5].value,
            address: editableFields[6].value
        };
        console.log('Updated profile data:', updatedData);
    }
});
// Photo upload functionality
document.getElementById('photoUpload').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            document.getElementById('profilePhoto').src = event.target.result;
        }
        reader.readAsDataURL(file);
    }
});