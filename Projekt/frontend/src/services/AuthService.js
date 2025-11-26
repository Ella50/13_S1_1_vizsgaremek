import axios from 'axios'

const API_URL = 'http://localhost:8000/api'

class AuthService {
  constructor() {
    this.api = axios.create({
      baseURL: API_URL,
      headers: {
        'Content-Type': 'application/json',
      },
    })

    // Request interceptor
    this.api.interceptors.request.use(
      (config) => {
        const token = localStorage.getItem('auth_token')
        if (token) {
          config.headers.Authorization = `Bearer ${token}`
        }
        return config
      },
      (error) => {
        return Promise.reject(error)
      }
    )

    // Response interceptor
    this.api.interceptors.response.use(
      (response) => response,
      (error) => {
        if (error.response?.status === 401) {
          this.logout()
          if (window.location.pathname !== '/login') {
            window.location.href = '/login'
          }
        }
        return Promise.reject(error)
      }
    )
  }

  async login(credentials) {
    try {
      const response = await this.api.post('/login', credentials)
      
      if (response.data.token) {
        localStorage.setItem('auth_token', response.data.token)
        localStorage.setItem('user', JSON.stringify(response.data.user))
      }
      
      return response.data
    } catch (error) {
      throw error.response?.data || error
    }
  }

  async register(userData) {
    try {
      const response = await this.api.post('/register', userData)
      
      if (response.data.token) {
        localStorage.setItem('auth_token', response.data.token)
        localStorage.setItem('user', JSON.stringify(response.data.user))
      }
      
      return response.data
    } catch (error) {
      throw error.response?.data || error
    }
  }

  logout() {
    // API hívás a token törléséhez a backend-en
    if (this.getToken()) {
      this.api.post('/logout').catch(error => {
        console.error('Logout API error:', error)
      })
    }
    
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user')
    delete this.api.defaults.headers.common['Authorization']
  }

  getToken() {
    return localStorage.getItem('auth_token')
  }

  getUser() {
    const user = localStorage.getItem('user')
    return user ? JSON.parse(user) : null
  }

  isAuthenticated() {
    return !!this.getToken()
  }

  async getCurrentUser() {
    try {
      const response = await this.api.get('/user')
      return response.data
    } catch (error) {
      this.logout()
      throw error
    }
  }
  async getCities() {
  try {
    const response = await this.api.get('/cities');
    return response.data;
  } catch (error) {
    throw error.response?.data || error;
  }
}
}

export default new AuthService()