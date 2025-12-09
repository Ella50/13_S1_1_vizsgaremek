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
    if (this.getToken()) {
      this.api.post('/logout').catch(error => {
        console.error('Logout API error:', error)
      })
    }
    
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user')

    axios.post('http://localhost:8000/api/logout')
            .catch(() => {})


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
    const token = localStorage.getItem('auth_token')
    console.log('AuthService.isAuthenticated():', {
        hasToken: !!token,
        token: token ? token.substring(0, 20) + '...' : null
    })
    return !!token
  }

// Getters
  getUserRole() {
    const user = this.getUser()
    return user?.userType || null
  }

  isAdmin() {
    return this.getUserRole() === 'Admin'
  }

  isKitchen() {
    return this.getUserRole() === 'Konyha'
  }

  isStudent() {
    return this.getUserRole() === 'Tanuló'
  }

  isTeacher() {
    return this.getUserRole() === 'Tanár'
  }

  isEmployee() {
    return this.getUserRole() === 'Dolgozó'
  }

  isExternal() {
    return this.getUserRole() === 'Külsős'
  }

  canViewMenu() {
    const allowedRoles = ['Tanuló', 'Tanár', 'Dolgozó', 'Külsős']
    return allowedRoles.includes(this.getUserRole())
  }

  getRoleInfo() {
    const role = this.getUserRole()
    return {
      role,
      isAdmin: role === 'Admin',
      isKitchen: role === 'Konyha',
      isStudent: role === 'Tanuló',
      isTeacher: role === 'Tanár',
      isEmployee: role === 'Dolgozó',
      isExternal: role === 'Külsős',
      canViewMenu: ['Tanuló', 'Tanár', 'Dolgozó', 'Külsős'].includes(role),
      displayName: role === 'Tanuló' ? 'Tanuló' : 
                   role === 'Tanár' ? 'Tanár' : role
    }
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