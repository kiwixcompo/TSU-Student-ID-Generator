import { verifyStudent } from '@/lib/actions';
import { ShieldCheck, XCircle } from 'lucide-react';
import Link from 'next/link';

export default async function VerifyPage({ params }: { params: Promise<{ reg: string }> }) {
  const { reg } = await params;
  const decodedReg = decodeURIComponent(reg);
  
  const student = await verifyStudent(decodedReg);

  const isSandwich = student?.programme === 'Sandwich';
  const primaryColor = isSandwich ? '#946F46' : '#166534';
  const darkPrimaryColor = isSandwich ? '#735431' : '#14532d';

  return (
    <div className="min-h-screen bg-slate-50 flex flex-col justify-center items-center p-4 font-sans">
      <div className="max-w-md w-full bg-white rounded-lg shadow-2xl overflow-hidden border border-slate-300 relative">
        <div className="absolute top-0 left-0 w-full h-1" style={{ backgroundColor: isSandwich ? primaryColor : '#16a34a' }}></div>
        <div className="bg-slate-50/50 pt-8 pb-4 text-center border-b border-slate-200">
          <div 
            className="w-16 h-16 text-white rounded-lg flex items-center justify-center font-bold mx-auto mb-3 shadow-sm border border-b-4"
            style={{ backgroundColor: primaryColor, borderColor: darkPrimaryColor }}
          >
            TSU
          </div>
          <h1 className="text-xl font-extrabold text-slate-800 tracking-tight uppercase">Taraba State University</h1>
          <p className="text-[10px] uppercase tracking-widest text-slate-500 font-bold mt-1">ID Verification System</p>
        </div>

        {student ? (
          <div className="p-6">
            <div 
              className="flex px-4 py-3 rounded mb-6 items-center justify-center shadow-sm border"
              style={{
                backgroundColor: isSandwich ? '#FAF4ED' : '#f0fdf4',
                borderColor: isSandwich ? '#C4B19D' : '#bbf7d0',
                color: isSandwich ? darkPrimaryColor : '#166534'
              }}
            >
              <ShieldCheck className="mr-2" size={24} style={{ color: isSandwich ? primaryColor : '#15803d' }} />
              <span className="font-extrabold text-lg uppercase tracking-wider">Verified Student</span>
            </div>

            <div className="flex flex-col items-center">
               <div className="w-32 h-32 mb-4 relative rounded border border-slate-200 overflow-hidden bg-slate-100 shadow-sm p-1">
                  <img 
                    src={student.passport_photo} 
                    alt="Student image" 
                    className="w-full h-full object-contain object-center rounded-sm bg-white"
                  />
                  {student.status === 'id_generated' ? (
                     <div 
                       className="absolute -bottom-2 -right-2 text-white rounded p-1 border"
                       style={{ backgroundColor: primaryColor, borderColor: darkPrimaryColor }}
                       title="ID Card Generated"
                     >
                       <ShieldCheck size={16} />
                     </div>
                  ) : null}
               </div>

               <h2 className="text-xl font-extrabold text-slate-800 text-center uppercase tracking-wide">
                 {student.first_name} {student.middle_name} {student.last_name}
               </h2>

               <div className="w-full mt-6 space-y-3 bg-white border border-slate-200 rounded p-4 text-sm shadow-sm">
                  <div className="flex justify-between border-b border-slate-100 pb-2">
                    <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Reg Number</span>
                    <span className="text-slate-800 font-bold font-mono">{student.reg_number}</span>
                  </div>
                  <div className="flex justify-between border-b border-slate-100 pb-2">
                    <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Programme</span>
                    <span className="font-bold uppercase" style={{ color: primaryColor }}>{student.programme}</span>
                  </div>
                  <div className="flex justify-between border-b border-slate-100 pb-2">
                    <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Faculty</span>
                    <span className="text-slate-800 font-semibold uppercase text-right pl-4">{student.faculty}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Course</span>
                    <span className="text-slate-800 font-semibold uppercase text-right pl-4">{student.course_of_study || student.department}</span>
                  </div>
               </div>
            </div>
          </div>
        ) : (
          <div className="p-8 text-center">
            <XCircle className="mx-auto text-red-600 mb-4" size={48} strokeWidth={1.5} />
            <h2 className="text-xl font-extrabold text-slate-800 mb-2 uppercase tracking-wide">Invalid ID</h2>
            <p className="text-xs text-slate-500 font-medium leading-relaxed">This registration number could not be found in our database. The ID card may be fraudulent or expired.</p>
            <div className="mt-6 bg-red-50 text-red-700 font-mono text-sm py-2 px-4 rounded border border-red-200 inline-block break-all shadow-sm">
              {decodedReg}
            </div>
          </div>
        )}
        
        <div className="bg-slate-50/50 px-6 py-4 border-t border-slate-200 text-center">
           <Link href="/" className="text-[10px] text-slate-500 hover:underline font-bold tracking-widest uppercase">
             Back to System Home
           </Link>
        </div>
      </div>
    </div>
  );
}
