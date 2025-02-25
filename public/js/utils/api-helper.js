class ApiHelper {
    static async handleResponse(response) {
        const contentType = response.headers.get('content-type');
        
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Invalid server response format');
        }

        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || 'Server error');
        }

        return data;
    }

    static async handleError(error) {
        console.error('API Error:', error);
        if (error.name === 'TypeError' && error.message.includes('JSON')) {
            throw new Error('Invalid server response format');
        }
        throw error;
    }
}
