'use client';

import { useState, useMemo } from 'react';
import { useRouter } from 'next/navigation';
import { submitStudentRegistration } from '@/lib/actions';
import { UploadCloud, CheckCircle, ArrowLeft } from 'lucide-react';
import Link from 'next/link';
import { tsuData } from '@/lib/tsu-data';

export default function Register() {
  const [photoPreview, setPhotoPreview] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [successMsg, setSuccessMsg] = useState('');
  const [errorMsg, setErrorMsg] = useState('');
  
  const [selectedFaculty, setSelectedFaculty] = useState('');
  const [selectedDepartment, setSelectedDepartment] = useState('');

  const currentFaculty = useMemo(() => 
    tsuData.find(f => f.faculty === selectedFaculty), 
  [selectedFaculty]);

  const currentDepartment = useMemo(() => 
    currentFaculty?.departments.find(d => d.name === selectedDepartment), 
  [currentFaculty, selectedDepartment]);

  const handlePhotoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      if (file.size > 2 * 1024 * 1024) {
        setErrorMsg('Image must be less than 2MB');
        return;
      }
      const reader = new FileReader();
      reader.onloadend = () => {
        setPhotoPreview(reader.result as string);
        setErrorMsg('');
      };
      reader.readAsDataURL(file);
    }
  };

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setLoading(true);
    setErrorMsg('');
    
    if (!photoPreview) {
      setErrorMsg('Please upload a passport photo.');
      setLoading(false);
      return;
    }

    const formData = new FormData(e.currentTarget);
    formData.append('passport_photo', photoPreview);

    const result = await submitStudentRegistration(formData);
    if (result.success) {
      setSuccessMsg(result.message || 'Success!');
      setPhotoPreview(null);
      (e.target as HTMLFormElement).reset();
    } else {
      setErrorMsg(result.error || 'Something went wrong.');
    }
    setLoading(false);
  };

  if (successMsg) {
    return (
      <div className="min-h-screen bg-gray-50 flex flex-col justify-center items-center p-4">
        <div className="max-w-md w-full bg-white rounded-xl shadow-lg p-8 text-center border-t-8 border-[#008000]">
          <CheckCircle className="mx-auto text-[#008000] mb-4" size={64} />
          <h2 className="text-2xl font-bold text-gray-900 mb-2">Registration Successful</h2>
          <p className="text-gray-600 mb-6">{successMsg}</p>
          <Link href="/" className="inline-block bg-[#008000] text-white px-6 py-3 rounded-lg hover:bg-green-700 transition">
            Back to Home
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8 font-sans">
      <div className="max-w-2xl mx-auto">
        <Link href="/" className="inline-flex items-center text-slate-500 hover:text-green-700 font-bold mb-6 hover:underline text-sm uppercase tracking-wider">
          <ArrowLeft className="mr-2" size={16} /> Back to Home
        </Link>
        <div className="bg-white rounded-xl shadow-sm overflow-hidden border border-slate-200">
          <div className="px-8 py-6 border-b border-slate-100 bg-slate-50/50">
            <h2 className="text-2xl font-bold text-slate-800 flex items-center gap-2 italic">Student Portal</h2>
            <p className="mt-1 text-xs text-slate-500 font-sans not-italic">Register for your Sandwich or IDELL ID Card.</p>
          </div>
          
          <div className="p-8">
            {errorMsg && (
              <div className="mb-6 p-4 bg-red-50 text-red-700 rounded-md border border-red-200 text-sm font-medium">
                {errorMsg}
              </div>
            )}
            
            <form onSubmit={handleSubmit} className="space-y-6 text-sm">
              <div>
                <label className="block text-[10px] uppercase font-bold text-slate-500 mb-2">Programme Type *</label>
                <div className="flex gap-6">
                  <label className="flex items-center cursor-pointer">
                    <input type="radio" name="programme" value="Sandwich" required className="w-4 h-4 text-green-600 focus:ring-green-600 border-slate-300" />
                    <span className="ml-2 font-medium text-slate-700">Sandwich</span>
                  </label>
                  <label className="flex items-center cursor-pointer">
                    <input type="radio" name="programme" value="IDELL" required className="w-4 h-4 text-green-600 focus:ring-green-600 border-slate-300" />
                    <span className="ml-2 font-medium text-slate-700">IDELL</span>
                  </label>
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div>
                  <label className="block text-[10px] uppercase font-bold text-slate-500 mb-2">First Name *</label>
                  <input type="text" name="first_name" required className="w-full px-3 py-2 border border-slate-300 rounded focus:outline-none focus:ring-1 focus:ring-green-600 text-sm text-slate-800" />
                </div>
                <div>
                  <label className="block text-[10px] uppercase font-bold text-slate-500 mb-2">Middle Name</label>
                  <input type="text" name="middle_name" className="w-full px-3 py-2 border border-slate-300 rounded focus:outline-none focus:ring-1 focus:ring-green-600 text-sm text-slate-800" />
                </div>
                <div>
                  <label className="block text-[10px] uppercase font-bold text-slate-500 mb-2">Last Name *</label>
                  <input type="text" name="last_name" required className="w-full px-3 py-2 border border-slate-300 rounded focus:outline-none focus:ring-1 focus:ring-green-600 text-sm text-slate-800" />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                  <label className="block text-[10px] uppercase font-bold text-slate-500 mb-2">Reg Number *</label>
                  <input type="text" name="reg_number" required placeholder="e.g. TSU/SW/2023/001" className="w-full px-3 py-2 border border-slate-300 rounded focus:outline-none focus:ring-1 focus:ring-green-600 text-sm text-slate-800 font-mono" />
                </div>
                <div>
                  <label className="block text-[10px] uppercase font-bold text-slate-500 mb-2">Blood Group *</label>
                  <select name="blood_group" required className="w-full px-3 py-2 border border-slate-300 rounded focus:outline-none focus:ring-1 focus:ring-green-600 text-sm text-slate-800 bg-white">
                    <option value="">Select Blood Group</option>
                    <option value="A+">A+</option>
                    <option value="A-">A-</option>
                    <option value="B+">B+</option>
                    <option value="B-">B-</option>
                    <option value="O+">O+</option>
                    <option value="O-">O-</option>
                    <option value="AB+">AB+</option>
                    <option value="AB-">AB-</option>
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                  <label className="block text-[10px] uppercase font-bold text-slate-500 mb-2">Faculty *</label>
                  <select 
                    name="faculty" 
                    required 
                    value={selectedFaculty}
                    onChange={(e) => {
                      setSelectedFaculty(e.target.value);
                      setSelectedDepartment(''); // reset department when faculty changes
                    }}
                    className="w-full px-3 py-2 border border-slate-300 rounded focus:outline-none focus:ring-1 focus:ring-green-600 text-sm text-slate-800 bg-white"
                  >
                    <option value="">Select Faculty</option>
                    {tsuData.map(f => (
                      <option key={f.faculty} value={f.faculty}>{f.faculty}</option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="block text-[10px] uppercase font-bold text-slate-500 mb-2">Department *</label>
                  <select 
                    name="department" 
                    required 
                    value={selectedDepartment}
                    onChange={(e) => setSelectedDepartment(e.target.value)}
                    disabled={!selectedFaculty}
                    className="w-full px-3 py-2 border border-slate-300 rounded focus:outline-none focus:ring-1 focus:ring-green-600 text-sm text-slate-800 bg-white disabled:bg-slate-100 disabled:text-slate-400"
                  >
                    <option value="">Select Department</option>
                    {currentFaculty?.departments.map(d => (
                      <option key={d.name} value={d.name}>{d.name}</option>
                    ))}
                  </select>
                </div>
              </div>

              {currentDepartment && currentDepartment.programmes.length > 0 && (
                <div>
                  <label className="block text-[10px] uppercase font-bold text-slate-500 mb-2">Course of Study *</label>
                  <select 
                    name="course_of_study" 
                    required 
                    className="w-full px-3 py-2 border border-slate-300 rounded focus:outline-none focus:ring-1 focus:ring-green-600 text-sm text-slate-800 bg-white"
                  >
                    <option value="">Select Course of Study</option>
                    {currentDepartment.programmes.map(p => (
                      <option key={p} value={p}>{p}</option>
                    ))}
                  </select>
                </div>
              )}

              <div>
                <label className="block text-[10px] uppercase font-bold text-slate-500 mb-2">Passport Photo *</label>
                <div className="flex items-center space-x-6">
                  {photoPreview ? (
                    <div className="relative w-24 h-24 rounded overflow-hidden border-2 border-green-600 shadow-sm">
                      <img src={photoPreview} alt="Preview" className="object-cover w-full h-full" />
                    </div>
                  ) : (
                    <div className="w-24 h-24 border-2 border-dashed border-slate-300 rounded bg-slate-50 flex flex-col items-center justify-center text-slate-400">
                      <UploadCloud size={24} />
                      <span className="text-[10px] mt-1 font-bold">UPLOAD</span>
                    </div>
                  )}
                  <div className="flex-1">
                    <input 
                      type="file" 
                      accept="image/*" 
                      onChange={handlePhotoChange}
                      className="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-xs file:font-bold file:bg-green-100 file:text-green-800 hover:file:bg-green-200 transition cursor-pointer"
                    />
                    <p className="mt-2 text-[10px] text-slate-400 uppercase font-bold">Square images preferred. Max size: 2MB.</p>
                  </div>
                </div>
              </div>

              <div className="pt-6 mt-6 border-t border-slate-100">
                <button
                  type="submit"
                  disabled={loading}
                  className="w-full bg-green-600 text-white font-bold py-2.5 px-4 rounded hover:bg-green-700 focus:outline-none shadow-sm transition disabled:opacity-50 text-sm tracking-wide uppercase"
                >
                  {loading ? 'Submitting...' : 'Submit Registration'}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  );
}
