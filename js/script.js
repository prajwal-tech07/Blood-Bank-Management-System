// script.js - General JavaScript for the Blood Bank Management System

document.addEventListener('DOMContentLoaded', function() {
    // Form validation for donor registration
    const donorForm = document.getElementById('donorForm');
    if (donorForm) {
        donorForm.addEventListener('submit', function(event) {
            const dob = new Date(document.getElementById('dob').value);
            const today = new Date();
            const age = today.getFullYear() - dob.getFullYear();
            
            // Check if donor is at least 18 years old
            if (age < 18) {
                event.preventDefault();
                alert('You must be at least 18 years old to register as a donor.');
                return false;
            }
            
            // Check if last donation was at least 3 months ago
            const lastDonation = document.getElementById('lastDonation').value;
            if (lastDonation) {
                const lastDonationDate = new Date(lastDonation);
                const threeMonthsAgo = new Date();
                threeMonthsAgo.setMonth(today.getMonth() - 3);
                
                if (lastDonationDate > threeMonthsAgo) {
                    event.preventDefault();
                    alert('You must wait at least 3 months between blood donations.');
                    return false;
                }
            }
        });
    }
    
    // Form validation for blood request
    const bloodRequestForm = document.getElementById('bloodRequestForm');
    if (bloodRequestForm) {
        bloodRequestForm.addEventListener('submit', function(event) {
            const requiredDate = new Date(document.getElementById('requiredDate').value);
            const today = new Date();
            
            // Check if required date is not in the past
            if (requiredDate < today) {
                event.preventDefault();
                alert('Required date cannot be in the past.');
                return false;
            }
        });
    }
    
    // Form validation for recipient registration
    const recipientForm = document.getElementById('recipientForm');
    if (recipientForm) {
        recipientForm.addEventListener('submit', function(event) {
            // Basic validation can be added here
        });
    }
    
    // Form validation for login
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            // Basic validation can be added here
        });
    }
});
