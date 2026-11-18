import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { AuthProvider } from './context/AuthContext.jsx'
import AppLayout from './components/AppLayout.jsx'
import ProtectedRoute from './components/ProtectedRoute.jsx'
import AuthPage from './pages/AuthPage.jsx'
import IncidenciasPage from './pages/IncidenciasPage.jsx'
import IncidenciaFormPage from './pages/IncidenciaFormPage.jsx'
import AdminPage from './pages/AdminPage.jsx'
import ProfilePage from './pages/ProfilePage.jsx'
import EditProfilePage from './pages/EditProfilePage.jsx'

function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route path="/login" element={<AuthPage />} />
          <Route path="/registro" element={<AuthPage registerMode />} />
          <Route element={<ProtectedRoute />}>
            <Route element={<AppLayout />}>
              <Route index element={<Navigate to="/incidencias" replace />} />
              <Route path="incidencias" element={<IncidenciasPage />} />
              <Route path="incidencias/nueva" element={<IncidenciaFormPage />} />
              <Route path="perfil" element={<ProfilePage />} />
              <Route path="perfil/editar" element={<EditProfilePage />} />
              <Route element={<ProtectedRoute adminOnly />}>
                <Route path="admin" element={<AdminPage />} />
              </Route>
              <Route path="*" element={<Navigate to="/incidencias" replace />} />
            </Route>
          </Route>
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  )
}

export default App
