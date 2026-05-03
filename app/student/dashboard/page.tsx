import { getStudentSession, verifyStudent, studentLogout } from '@/lib/actions';
import { redirect } from 'next/navigation';
import Link from 'next/link';
import { LogOut, ShieldCheck, Clock, User, MessageSquare } from 'lucide-react';

export default async function StudentDashboard() {
  const session = await getStudentSession();
  if (!session) {
    redirect('/student/login');
  }

  const student = await verifyStudent(session.reg_number);
  
  if (!student) {
    return (
      <div className="min-h-screen bg-slate-50 flex flex-col justify-center items-center font-sans p-4">
        <p className="text-red-500">Student not found in database.</p>
        <Link href="/student/login" className="mt-4 text-green-600">Back to Login</Link>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-50 font-sans pb-12">
      <nav className="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-10">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16">
            <div className="flex items-center">
              <div className="flex-shrink-0 flex items-center mt-1">
                 <div className="w-8 h-8 bg-green-800 rounded flex items-center justify-center text-white font-bold text-xs shadow-sm mr-2 border-b-2 border-green-900">
                   TSU
                 </div>
                 <span className="font-bold text-slate-800 uppercase tracking-tight hidden sm:block">Student Portal</span>
              </div>
            </div>
            <div className="flex items-center gap-4">
              <div className="text-right hidden sm:block">
                <span className="block text-xs font-bold text-slate-800 uppercase">{student.first_name} {student.last_name}</span>
                <span className="block text-[10px] text-slate-500 font-mono">{student.reg_number}</span>
              </div>
              <form action={studentLogout}>
                <button type="submit" className="inline-flex items-center px-3 py-1.5 border border-slate-200 text-xs font-bold rounded text-slate-600 bg-white hover:bg-slate-50 hover:text-red-700 transition uppercase tracking-wide">
                  <LogOut className="mr-1.5 h-3.5 w-3.5" />
                  Logout
                </button>
              </form>
            </div>
          </div>
        </div>
      </nav>

      <main className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
        <h1 className="text-2xl font-bold text-slate-800 uppercase tracking-tight mb-6">Dashboard</h1>

        <div className="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden mb-6">
          <div className="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
            <h2 className="text-base font-bold text-slate-800 flex items-center uppercase tracking-wide">
              <User className="mr-2 text-green-600" size={18} />
              Registration Details
            </h2>
            <span className={`px-2.5 py-1 rounded text-[10px] font-bold uppercase tracking-wider ${
              student.status === 'id_generated' 
                ? 'bg-green-100 text-green-800 border border-green-200' 
                : 'bg-amber-100 text-amber-800 border border-amber-200'
            }`}>
              {student.status === 'pending' ? 'Pending Review' : 'ID Generated'}
            </span>
          </div>
          <div className="p-6">
            <div className="flex flex-col md:flex-row gap-6 items-start">
              <div className="w-24 h-24 sm:w-32 sm:h-32 shrink-0 rounded border border-slate-200 overflow-hidden bg-slate-100 p-1 shadow-sm">
                <img src={student.passport_photo} alt="Passport" className="w-full h-full object-cover rounded-sm" />
              </div>
              
              <div className="flex-1 w-full text-sm">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                  <div>
                    <span className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Full Name</span>
                    <span className="font-semibold text-slate-800 uppercase">{student.first_name} {student.middle_name} {student.last_name}</span>
                  </div>
                  <div>
                    <span className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Registration Number</span>
                    <span className="font-semibold font-mono text-slate-800">{student.reg_number}</span>
                  </div>
                  <div>
                    <span className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Programme</span>
                    <span className="font-semibold text-slate-800 uppercase">{student.programme}</span>
                  </div>
                  <div>
                    <span className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Faculty</span>
                    <span className="font-semibold text-slate-800 uppercase">{student.faculty}</span>
                  </div>
                  <div className="sm:col-span-2">
                    <span className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Course of Study / Department</span>
                    <span className="font-semibold text-slate-800 uppercase">{student.course_of_study || student.department}</span>
                  </div>
                  <div>
                     <span className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Blood Group</span>
                     <span className="font-semibold text-red-600 bg-red-50 border border-red-100 px-2 py-0.5 rounded inline-block text-xs mt-1">{student.blood_group}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {student.admin_note && (
          <div className="bg-blue-50 rounded-lg shadow-sm border border-blue-200 overflow-hidden mb-6">
            <div className="px-6 py-4 border-b border-blue-100 flex items-center justify-between">
              <h2 className="text-base font-bold text-blue-900 flex items-center uppercase tracking-wide">
                <MessageSquare className="mr-2 text-blue-600" size={18} />
                Admin Communication
              </h2>
              <span className="px-2.5 py-1 rounded text-[10px] font-bold uppercase tracking-wider bg-blue-100 text-blue-800 border border-blue-200">
                Action Required / Note
              </span>
            </div>
            <div className="p-6 text-sm text-blue-800 whitespace-pre-wrap">
              {student.admin_note}
            </div>
          </div>
        )}

        <div className="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
          <div className="p-8 text-center flex flex-col items-center">
            {student.status === 'id_generated' ? (
              <>
                <ShieldCheck className="text-green-600 mb-4" size={48} />
                <h3 className="text-lg font-bold text-slate-800 uppercase tracking-wide mb-2">ID Card Ready</h3>
                <p className="text-sm text-slate-500 mb-6 max-w-md mx-auto leading-relaxed">
                  Your ID card has been generated and printed. Please visit the <span className="font-bold text-slate-700">Security Unit</span> at the university to collect your physical ID card.
                </p>
                <div className="bg-slate-50 border border-slate-200 px-6 py-4 rounded w-full max-w-sm">
                  <p className="text-[10px] text-slate-500 font-bold uppercase tracking-widest mb-1">Verify Your Status Online:</p>
                  <Link href={`/verify/${encodeURIComponent(student.reg_number)}`} className="text-sm font-semibold text-green-700 hover:underline">
                    View Verification Page
                  </Link>
                </div>
              </>
            ) : (
              <>
                <Clock className="text-amber-500 mb-4" size={48} />
                <h3 className="text-lg font-bold text-slate-800 uppercase tracking-wide mb-2">Processing Your ID</h3>
                <p className="text-sm text-slate-500 max-w-md mx-auto leading-relaxed">
                  Your details have been submitted and are currently pending review by the administration. Check back later to see if your ID card has been generated.
                </p>
              </>
            )}
          </div>
        </div>
      </main>
    </div>
  );
}
