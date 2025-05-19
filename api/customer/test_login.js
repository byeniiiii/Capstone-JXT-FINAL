/* 
 * Login API Test Script
 * Save this file in your project and run it with Node.js
 * Usage: node test_login.js
 */

const axios = require('axios');

// Configuration
const API_URL = 'http://localhost/capstone_jxt/api/customer/login_api.php';

// Test credentials - replace with valid credentials from your database
const VALID_USER = {
  username: 'lando', // This is a valid user from your database
  password: 'password123' // Replace with actual password
};

const INVALID_USER = {
  username: 'nonexistent_user',
  password: 'wrong_password'
};

// Function to test login
async function testLogin(user) {
  try {
    console.log(`Testing login with username: ${user.username}`);
    const response = await axios.post(API_URL, user);
    
    console.log('Response Status:', response.status);
    console.log('Response Data:', JSON.stringify(response.data, null, 2));
    
    return response.data;
  } catch (error) {
    console.error('Error Status:', error.response?.status);
    console.error('Error Data:', JSON.stringify(error.response?.data, null, 2));
    return error.response?.data;
  }
}

// Run tests
async function runTests() {
  console.log('==== TESTING VALID USER LOGIN ====');
  await testLogin(VALID_USER);
  
  console.log('\n==== TESTING INVALID USER LOGIN ====');
  await testLogin(INVALID_USER);
}

runTests().then(() => console.log('Testing completed.'));
