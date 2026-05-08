import Link from 'next/link';
import { ShieldAlert, UserPlus, UserCheck } from 'lucide-react';

export default function Home() {
  return (
    <div className="min-h-screen bg-slate-50 flex flex-col justify-center items-center p-4 font-sans">
      <div className="max-w-3xl w-full bg-white rounded-xl shadow-sm border border-slate-200 p-8 md:p-12 text-center relative overflow-hidden">
        <div className="absolute top-0 left-0 w-full h-1 bg-green-600"></div>
        <div className="mb-8 flex justify-center">
          <img 
            src="/tsu-logo.png" 
            alt="Taraba State University Logo" 
            className="w-20 h-20 object-contain drop-shadow-md"
            onError={(e) => {
              e.currentTarget.style.display = 'none';
              const fallback = document.createElement('div');
              fallback.className = 'w-16 h-16 bg-green-800 rounded-lg flex items-center justify-center text-white font-bold text-xl shadow-sm border border-green-900 border-b-4';
              fallback.textContent = 'TSU';
              e.currentTarget.parentElement?.appendChild(fallback);
            }}
          />
        </div>
        <h1 className="text-3xl md:text-4xl font-extrabold text-slate-800 mb-2 tracking-tight uppercase">
          Taraba State University
        </h1>
        <p className="text-sm text-slate-500 mb-10 font-bold uppercase tracking-widest">
          Student ID Card Generation Portal
        </p>
        
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 w-full max-w-4xl mx-auto">
          <Link href="/register" className="group block p-6 border border-slate-200 rounded-lg hover:border-green-600 hover:shadow-sm transition-all text-left bg-slate-50/50 hover:bg-green-50/30">
            <h3 className="text-base font-bold text-slate-800 group-hover:text-green-700 mb-2 flex items-center uppercase tracking-wide">
              <UserPlus className="mr-3 text-green-600" size={18} />
              Register
            </h3>
            <p className="text-xs text-slate-600 leading-relaxed font-medium">New students register and upload details.</p>
          </Link>

          <Link href="/student/login" className="group block p-6 border border-slate-200 rounded-lg hover:border-green-600 hover:shadow-sm transition-all text-left bg-slate-50/50 hover:bg-green-50/30">
            <h3 className="text-base font-bold text-slate-800 group-hover:text-green-700 mb-2 flex items-center uppercase tracking-wide">
              <UserCheck className="mr-3 text-green-600" size={18} />
              Login
            </h3>
            <p className="text-xs text-slate-600 leading-relaxed font-medium">Returning students check ID status.</p>
          </Link>

          <Link href="/admin" className="group block p-6 border border-slate-200 rounded-lg hover:border-green-600 hover:shadow-sm transition-all text-left bg-slate-50/50 hover:bg-green-50/30">
            <h3 className="text-base font-bold text-slate-800 group-hover:text-green-700 mb-2 flex items-center uppercase tracking-wide">
              <ShieldAlert className="mr-3 text-green-600" size={18} />
              Admin
            </h3>
            <p className="text-xs text-slate-600 leading-relaxed font-medium">Staff review and generate ID cards.</p>
          </Link>
        </div>
      </div>
      
      <div className="mt-8 text-center text-[10px] text-slate-400 font-bold tracking-widest uppercase">
        <p>&quot;Harnessing Nature&apos;s Gift&quot;</p>
      </div>
    </div>
  );
}
