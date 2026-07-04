import { Route, Routes } from 'react-router-dom'

import { AppLayout } from '@/layouts/AppLayout'
import { DashboardLayout } from '@/layouts/DashboardLayout'
import { ApplyPage } from '@/pages/ApplyPage'
import { CreateEventPage } from '@/pages/CreateEventPage'
import { EditEventPage } from '@/pages/EditEventPage'
import { EventDetailPage } from '@/pages/EventDetailPage'
import { EventsPage } from '@/pages/EventsPage'
import { HomePage } from '@/pages/HomePage'
import { LoginPage } from '@/pages/LoginPage'
import { LogoutPage } from '@/pages/LogoutPage'
import { NotFoundPage } from '@/pages/NotFoundPage'
import { OrganizerApplicantsPage } from '@/pages/OrganizerApplicantsPage'
import { OrganizerCertificatesPage } from '@/pages/OrganizerCertificatesPage'
import { OrganizerDashboardPage } from '@/pages/OrganizerDashboardPage'
import { VerifyCertificatePage } from '@/pages/VerifyCertificatePage'
import { VolunteerDashboardPage } from '@/pages/VolunteerDashboardPage'
import { RequireAuth } from '@/routes/RequireAuth'

export function AppRoutes() {
  return (
    <Routes>
      <Route element={<AppLayout />}>
        <Route index element={<HomePage />} />
        <Route path="events" element={<EventsPage />} />
        <Route path="events/:slug" element={<EventDetailPage />} />
        <Route path="login" element={<LoginPage />} />
        <Route path="logout" element={<LogoutPage />} />
        <Route path="verify/:credentialId" element={<VerifyCertificatePage />} />
      </Route>

      <Route
        element={
          <RequireAuth capability="volunteer">
            <DashboardLayout area="volunteer" />
          </RequireAuth>
        }
      >
        <Route path="volunteer/dashboard" element={<VolunteerDashboardPage />} />
        <Route path="volunteer/events" element={<EventsPage viewer="volunteer" />} />
        <Route path="volunteer/apply/:eventId" element={<ApplyPage />} />
        <Route path="volunteer/events/:slug" element={<EventDetailPage viewer="volunteer" />} />
      </Route>

      <Route
        element={
          <RequireAuth capability="organizer">
            <DashboardLayout area="organizer" />
          </RequireAuth>
        }
      >
        <Route path="organizer" element={<OrganizerDashboardPage />} />
        <Route path="organizer/applicants" element={<OrganizerApplicantsPage />} />
        <Route path="organizer/certificates" element={<OrganizerCertificatesPage />} />
        <Route path="organizer/events" element={<EventsPage viewer="organizer" />} />
        <Route path="organizer/events/:slug" element={<EventDetailPage viewer="organizer" />} />
        <Route path="organizer/events/:eventId/edit" element={<RequireAuth capability="manageOrganizer"><EditEventPage /></RequireAuth>} />
        <Route path="organizer/create" element={<RequireAuth capability="manageOrganizer"><CreateEventPage /></RequireAuth>} />
      </Route>

      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  )
}
