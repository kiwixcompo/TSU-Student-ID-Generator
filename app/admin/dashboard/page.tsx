import { fetchStudentsForAdmin, getAdminSession, adminLogout } from '@/lib/actions';
import { redirect } from 'next/navigation';
import { LogOut } from 'lucide-react';
import AdminDashboardClient from './AdminDashboardClient';

export default async function AdminDashboard() {
  const session = await getAdminSession();
  
  if (!session) {
    redirect('/admin');
  }

  const students = await fetchStudentsForAdmin();

  return (
    <div className="min-h-screen bg-slate-50 flex flex-col font-sans">
      <header className="h-14 bg-green-800 text-white flex items-center justify-between px-6 shadow-md shrink-0">
        <div className="flex items-center gap-4">
          <div className="w-8 h-8 bg-white rounded-full flex items-center justify-center font-bold text-green-800 text-xs shadow-sm">TSU</div>
          <h1 className="text-lg font-bold tracking-tight uppercase hidden sm:block">Taraba State University · Student ID Portal</h1>
          <h1 className="text-lg font-bold tracking-tight uppercase sm:hidden">TSU · ID Portal</h1>
        </div>
        <div className="flex items-center gap-6">
          <div className="flex flex-col items-end hidden sm:flex">
            <span className="text-xs font-semibold opacity-90">Programme Manager</span>
            <span className="text-[10px] bg-white text-green-800 px-2 rounded font-bold uppercase">{session.programme_managed} PROGRAMME</span>
          </div>
          <form action="/admin/logout" className="flex items-center gap-4">
             <span className="text-sm font-medium">@{session.username}</span>
             <button formAction={async () => {
               'use server';
               await adminLogout();
             }} className="flex items-center text-xs bg-green-900 hover:bg-slate-900 px-3 py-1.5 rounded transition shadow-sm border border-transparent font-bold">
               <LogOut size={14} className="mr-2" /> <span className="hidden sm:inline">LOGOUT</span>
             </button>
          </form>
        </div>
      </header>

      <main className="flex-1 p-4 sm:p-6 flex flex-col gap-6 max-w-7xl mx-auto w-full">
         <AdminDashboardClient students={students} programmeManaged={session.programme_managed} />
      </main>
    </div>
  );
}
