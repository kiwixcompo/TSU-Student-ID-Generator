'use client';

import { useState } from 'react';
import { studentLogin } from '@/lib/actions';
import { UserCheck, ArrowLeft } from 'lucide-react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';

export default function StudentLogin() {
  const [errorMsg, setErrorMsg] = useState('');
  const [loading, setLoading] = useState(false);
  const router = useRouter();

  const handleAction = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setLoading(true);
    setErrorMsg('');
    const formData = new FormData(e.currentTarget);
    const result = await studentLogin(formData);
    
    if (result && !result.success) {
      setErrorMsg(result.error || 'Login failed');
      setLoading(false);
    } else if (result?.success) {
      router.push('/student/dashboard');
    } else {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans">
      <div className="absolute top-8 left-8">
        <Link href="/" className="inline-flex items-center text-slate-500 hover:text-green-700 hover:underline font-bold text-sm tracking-widest uppercase">
          <ArrowLeft className="mr-2" size={16} /> Back to Home
        </Link>
      </div>

      <div className="sm:mx-auto sm:w-full sm:max-w-md">
        <div className="mx-auto flex items-center justify-center h-16 w-16 rounded-lg bg-green-800 mb-6 shadow-sm border-b-4 border-green-900 text-white">
          <UserCheck className="h-8 w-8" />
        </div>
        <h2 className="mt-2 text-center text-2xl font-bold text-slate-800 uppercase tracking-tight">
          Student Login
        </h2>
        <p className="mt-1 text-center text-xs text-slate-500 uppercase tracking-widest font-bold">
          Check ID Status
        </p>
      </div>

      <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div className="bg-white py-8 px-4 shadow-sm sm:rounded-xl sm:px-10 border border-slate-200 relative overflow-hidden">
          <div className="absolute top-0 left-0 w-full h-1 bg-green-600"></div>
          {errorMsg && (
            <div className="mb-6 p-3 bg-red-50 text-red-700 rounded text-sm text-center font-bold">
              {errorMsg}
            </div>
          )}
          
          <form className="space-y-6" onSubmit={handleAction}>
            <div>
              <label htmlFor="reg_number" className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                Registration Number
              </label>
              <div className="mt-1">
                <input
                  id="reg_number"
                  name="reg_number"
                  type="text"
                  required
                  placeholder="TSU/SW/2023/..."
                  className="appearance-none block w-full px-3 py-2 border border-slate-300 rounded text-sm placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-green-600 focus:border-green-600 text-slate-800 font-mono"
                />
              </div>
            </div>

            <div>
              <label htmlFor="password" className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                Password
              </label>
              <p className="text-[10px] text-slate-400 mb-2">Use your Registration Number as your password.</p>
              <div className="mt-1">
                <input
                  id="password"
                  name="password"
                  type="password"
                  required
                  placeholder="TSU/SW/2023/..."
                  className="appearance-none block w-full px-3 py-2 border border-slate-300 rounded text-sm placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-green-600 focus:border-green-600 text-slate-800 font-mono"
                />
              </div>
            </div>

            <div>
              <button
                type="submit"
                disabled={loading}
                className="w-full flex justify-center py-2.5 px-4 border border-transparent rounded text-sm font-bold text-white bg-green-600 hover:bg-green-700 focus:outline-none shadow-sm disabled:opacity-50 uppercase tracking-wide"
              >
                {loading ? 'AUTHENTICATING...' : 'SIGN IN'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
