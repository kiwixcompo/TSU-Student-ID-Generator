'use client';

import { useState } from 'react';
import { adminLogin } from '@/lib/actions';
import { Shield, ArrowLeft } from 'lucide-react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';

export default function AdminLogin() {
  const [errorMsg, setErrorMsg] = useState('');
  const [loading, setLoading] = useState(false);
  const router = useRouter();

  const handleAction = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setLoading(true);
    setErrorMsg('');
    const formData = new FormData(e.currentTarget);
    const result = await adminLogin(formData);
    
    if (result && !result.success) {
      setErrorMsg(result.error || 'Login failed');
      setLoading(false);
    } else if (result?.success) {
      router.push('/admin/dashboard');
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
          <Shield className="h-8 w-8" />
        </div>
        <h2 className="mt-2 text-center text-2xl font-bold text-slate-800 uppercase tracking-tight">
          Admin Login
        </h2>
        <p className="mt-1 text-center text-xs text-slate-500 uppercase tracking-widest font-bold">
          System Access
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
              <label htmlFor="username" className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                Username
              </label>
              <div className="mt-1">
                <input
                  id="username"
                  name="username"
                  type="text"
                  required
                  defaultValue="admin"
                  className="appearance-none block w-full px-3 py-2 border border-slate-300 rounded text-sm placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-green-600 focus:border-green-600 text-slate-800"
                />
              </div>
            </div>

            <div>
              <label htmlFor="password" className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                Password
              </label>
              <div className="mt-1">
                <input
                  id="password"
                  name="password"
                  type="password"
                  required
                  defaultValue="password"
                  className="appearance-none block w-full px-3 py-2 border border-slate-300 rounded text-sm placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-green-600 focus:border-green-600 text-slate-800"
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
          
          <div className="mt-8 pt-6 border-t border-slate-100 text-[10px] text-slate-400 text-center uppercase font-bold tracking-wider">
            <p className="mb-2">DEMO ACCOUNTS:</p>
            <p>Admin: <span className="text-slate-600">admin</span> / <span className="text-slate-600">password</span></p>
            <p>Sandwich: <span className="text-slate-600">sandwich_admin</span> / <span className="text-slate-600">password</span></p>
            <p>IDELL: <span className="text-slate-600">idell_admin</span> / <span className="text-slate-600">password</span></p>
          </div>
        </div>
      </div>
    </div>
  );
}
