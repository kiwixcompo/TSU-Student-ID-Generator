'use client';

import { useRef, useState, useEffect } from 'react';
import type { Student } from '@/lib/db';
import { QRCodeSVG } from 'qrcode.react';
import { Download, ArrowLeft, Printer, CheckCircle } from 'lucide-react';
import { toPng } from 'html-to-image';
import Link from 'next/link';
import { markIdGenerated } from '@/lib/actions';

export default function IdCardClient({ student, baseUrl }: { student: Student, baseUrl: string }) {
  const frontRef = useRef<HTMLDivElement>(null);
  const backRef = useRef<HTMLDivElement>(null);
  const [downloading, setDownloading] = useState(false);
  const [generated, setGenerated] = useState(student.status === 'id_generated');

  const [logoSrc, setLogoSrc] = useState('/tsu-logo.png');
  const [passportSrc, setPassportSrc] = useState(student.passport_photo);

  useEffect(() => {
    const toDataURL = async (url: string) => {
      if (!url || url.startsWith('data:')) return url;
      try {
        const response = await fetch(url);
        const blob = await response.blob();
        return await new Promise<string>((resolve, reject) => {
          const reader = new FileReader();
          reader.onloadend = () => resolve(reader.result as string);
          reader.onerror = reject;
          reader.readAsDataURL(blob);
        });
      } catch (err) {
        console.warn('Failed to convert image to base64:', url, err);
        return url;
      }
    };

    Promise.all([
      toDataURL('/tsu-logo.png'),
      toDataURL(student.passport_photo)
    ]).then(([l, p]) => {
      setLogoSrc(l);
      setPassportSrc(p);
    });
  }, [student.passport_photo]);

  const verificationUrl = `${baseUrl}/verify/${encodeURIComponent(student.reg_number)}`;

  const handleDownload = async () => {
    try {
      setDownloading(true);
      if (frontRef.current && backRef.current) {
        // Generate Front
        const dataUrlFront = await toPng(frontRef.current, { pixelRatio: 3 });
        const linkFront = document.createElement('a');
        linkFront.download = `${student.reg_number.replaceAll('/', '_')}_front.png`;
        linkFront.href = dataUrlFront;
        linkFront.click();

        // Generate Back
        const dataUrlBack = await toPng(backRef.current, { pixelRatio: 3 });
        const linkBack = document.createElement('a');
        linkBack.download = `${student.reg_number.replaceAll('/', '_')}_back.png`;
        linkBack.href = dataUrlBack;
        linkBack.click();

        // Mark as generated
        if (!generated) {
          await markIdGenerated(student.reg_number);
          setGenerated(true);
        }
      }
    } catch (err) {
      console.error('Failed to generate image', err);
      alert('Failed to generate ID card images.');
    } finally {
      setDownloading(false);
    }
  };

  const handlePrint = () => {
    window.print();
    if (!generated) {
      markIdGenerated(student.reg_number).then(() => setGenerated(true));
    }
  };

  return (
    <div className="min-h-screen bg-gray-100 py-8 px-4 sm:px-6 lg:px-8 print:bg-white print:py-0 print:px-0 flex flex-col items-center">
      
      {/* Controls Container */}
      <div className="max-w-4xl w-full mb-8 flex flex-col sm:flex-row justify-between items-center bg-white p-4 rounded-lg shadow print:hidden">
        <Link href="/admin/dashboard" className="inline-flex items-center text-gray-600 hover:text-[#008000] mb-4 sm:mb-0 transition font-medium">
          <ArrowLeft size={18} className="mr-2" /> Back to Dashboard
        </Link>
        <div className="flex space-x-4">
          <button
            onClick={handlePrint}
            className="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md font-medium transition"
          >
            <Printer size={18} className="mr-2" /> Print
          </button>
          <button
            onClick={handleDownload}
            disabled={downloading}
            className="inline-flex items-center px-4 py-2 bg-[#008000] hover:bg-green-700 text-white rounded-md font-medium transition disabled:opacity-50"
          >
            <Download size={18} className="mr-2" /> {downloading ? 'Exporting...' : 'Export as PNG'}
          </button>
        </div>
      </div>

      {generated && (
        <div className="max-w-4xl w-full mb-8 bg-green-50 border border-green-200 text-green-700 p-4 rounded-lg flex items-center print:hidden">
          <CheckCircle className="mr-2" size={20} /> This ID card has already been generated or exported previously.
        </div>
      )}

      {/* Cards Container */}
      <div className="flex flex-col xl:flex-row gap-8 items-center justify-center w-full print:block print:w-auto font-sans">
        
        {/* FRONT OF CARD */}
        <div 
          ref={frontRef}
          className="id-card-wrapper bg-white shadow-2xl overflow-hidden flex flex-col relative print:shadow-none print:break-after-page text-left border border-slate-300 rounded-xl"
          style={{ width: '260px', height: '412px' }}
        >
          {/* Background Texture/Watermark */}
          <div className="absolute inset-0 opacity-[0.03] pointer-events-none" style={{ backgroundImage: 'radial-gradient(circle, #008000 1.5px, transparent 1.5px)', backgroundSize: '15px 15px' }}></div>
          <div className="absolute inset-0 flex items-center justify-center opacity-5 pointer-events-none z-0 overflow-hidden">
            <span className="text-green-900 text-[120px] font-black rotate-[-45deg] select-none tracking-tighter">TSU</span>
          </div>

          {/* Header */}
          <div className="flex flex-col items-center pt-5 z-10 px-2 shrink-0">
            
            {/* LOGO SECTION */}
            <img 
              src={logoSrc} 
              alt="TSU Logo" 
              className="w-16 h-16 object-contain mb-1.5 drop-shadow-md" 
              onError={(e) => { 
                e.currentTarget.src = 'https://via.placeholder.com/150/ffffff/008000?text=LOGO'; 
              }} 
            />
            
            <div className="text-center space-y-1">
              <p className="text-[13px] font-extrabold text-green-900 leading-none uppercase tracking-wide drop-shadow-sm">Taraba State University</p>
              <div className="inline-flex flex-col items-center">
                <p className="text-[10px] font-bold text-green-800 leading-none uppercase tracking-widest pb-0.5">Jalingo</p>
                <div className="w-12 h-[1.5px] bg-green-800 opacity-60"></div>
              </div>
            </div>
          </div>

          {/* Photo */}
          <div className="flex justify-center mt-2 z-10 shrink-0">
            <div className="relative p-0.5 bg-green-700 rounded shadow-md">
              <div className="w-28 h-32 border border-white rounded-sm bg-white overflow-hidden">
                <img 
                  src={passportSrc} 
                  alt="Student Passport"
                  className="w-full h-full object-cover"
                />
              </div>
            </div>
          </div>

          {/* Name & Title */}
          <div className="text-center mt-1 px-2 z-10 shrink-0 mb-1">
            <p className="text-[12px] font-extrabold text-green-900 uppercase leading-snug px-1 line-clamp-2 drop-shadow-sm">
              {student.last_name}, {student.first_name} {student.middle_name}
            </p>
            <p className="text-[9px] text-gray-700 font-bold uppercase mt-1 tracking-wider">
              Student / {student.course_of_study || student.department || student.programme}
            </p>
          </div>

          {/* Info Section with Vertical Bar */}
          <div className="flex flex-1 w-full relative z-10 items-stretch mb-1">
            {/* The vertical bar */}
            <div className="w-9 ml-3 bg-green-700 rounded-t-xl relative flex items-center justify-center shadow-inner overflow-hidden shrink-0 self-stretch mt-1.5">
              <div className="absolute inset-0 bg-gradient-to-tr from-black/20 to-transparent"></div>
              <span 
                className="text-white text-[8.5px] font-extrabold uppercase tracking-[0.08em] drop-shadow-sm whitespace-nowrap z-10 leading-[1.3]"
                style={{ writingMode: 'vertical-rl', transform: 'rotate(180deg)' }}
              >
                STUDENT ID CARD
              </span>
            </div>
            
            {/* Details */}
            <div className="flex-1 flex flex-col justify-end pt-1 pb-1.5 pl-3 pr-2 text-[10px] space-y-1.5">
              <div className="flex flex-col items-start leading-[1.1]">
                <span className="font-extrabold text-green-800 text-[10px] uppercase tracking-wider mb-0.5">Reg No:</span>
                <span className="text-gray-900 font-black uppercase text-[13px] break-all">{student.reg_number}</span>
              </div>
              <div className="flex flex-col items-start leading-[1.1]">
                <span className="font-extrabold text-green-800 text-[10px] uppercase tracking-wider mb-0.5">Faculty:</span>
                <span className="text-gray-900 font-bold text-[12px] leading-tight break-words pr-1">{student.faculty}</span>
              </div>
              <div className="flex flex-col items-start leading-[1.1]">
                <span className="font-extrabold text-green-800 text-[10px] uppercase tracking-wider mb-0.5">Dept:</span>
                <span className="text-gray-900 font-bold text-[12px] leading-tight break-words pr-1">{student.department || student.programme}</span>
              </div>
            </div>
          </div>

          {/* Bottom Bar */}
          <div className="h-8 bg-green-800 w-full flex items-center justify-center shrink-0 z-10 relative overflow-hidden">
            <div className="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent pointer-events-none"></div>
            <p className="text-[9px] text-white font-medium/80 z-10">Issued: {new Date().toLocaleDateString('en-US', { month: 'long', year: 'numeric'})}</p>
          </div>
        </div>

        {/* BACK OF CARD */}
        <div 
          ref={backRef}
          className="id-card-wrapper bg-white shadow-2xl overflow-hidden flex flex-col relative print:shadow-none print:break-after-page items-center pt-8 border border-slate-300 rounded-xl"
          style={{ width: '260px', height: '412px' }}
        >
          {/* Background Texture */}
          <div className="absolute inset-0 opacity-[0.02] pointer-events-none flex flex-col items-center justify-center space-y-8 z-0">
            <span className="text-green-900 text-[80px] font-black rotate-[-30deg] select-none tracking-tighter">TSU</span>
            <span className="text-green-900 text-[80px] font-black rotate-[-30deg] select-none tracking-tighter">TSU</span>
          </div>

          <p className="text-[12px] font-extrabold text-green-900 uppercase tracking-[0.05em] mb-3 z-10 text-center">
            Scan this to verify
          </p>
          
          <div className="border-[3px] border-green-800 rounded-xl p-2.5 bg-white z-10 shadow-sm relative">
            <QRCodeSVG 
              value={`Name: ${student.last_name}, ${student.first_name} ${student.middle_name || ''}\nReg No: ${student.reg_number}\nFaculty: ${student.faculty}\nDept: ${student.department || student.programme}\nVerify: ${verificationUrl}`} 
              size={130}
              level="M"
              fgColor="#14532d"
            />
            {/* Corner Accents */}
            <div className="absolute top-1 left-1 w-4 h-4 border-t-2 border-l-2 border-green-400 rounded-tl"></div>
            <div className="absolute top-1 right-1 w-4 h-4 border-t-2 border-r-2 border-green-400 rounded-tr"></div>
            <div className="absolute bottom-1 left-1 w-4 h-4 border-b-2 border-l-2 border-green-400 rounded-bl"></div>
            <div className="absolute bottom-1 right-1 w-4 h-4 border-b-2 border-r-2 border-green-400 rounded-br"></div>
          </div>

          <div className="border-[2px] border-red-600 rounded-lg py-1.5 px-8 mt-5 bg-white z-10 flex flex-col items-center shadow-sm">
            <span className="text-[10px] font-black text-red-600 uppercase tracking-widest leading-none">Blood Group</span>
            <span className="text-3xl font-black text-gray-800 leading-none mt-1.5 mb-1">{student.blood_group}</span>
          </div>

          <div className="mt-auto mb-4 text-center z-10">
            <p className="text-[9px] text-gray-500 mb-1">If found, please return to:</p>
            <p className="text-[11px] font-black text-green-800 uppercase tracking-wide">SECURITY UNIT</p>
            <p className="text-[9px] text-gray-600">Taraba State University</p>
          </div>

          {/* Bottom Bar */}
          <div className="h-10 bg-green-800 w-full flex items-center justify-center shrink-0 z-10 relative overflow-hidden">
            <div className="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent pointer-events-none"></div>
            <p className="text-[9px] text-white font-medium z-10">Property of Taraba State University</p>
          </div>
        </div>

      </div>
    </div>
  );
}