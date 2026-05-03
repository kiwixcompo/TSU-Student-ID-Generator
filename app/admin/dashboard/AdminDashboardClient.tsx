'use client';

import { useState, useMemo } from 'react';
import type { Student } from '@/lib/db';
import Link from 'next/link';
import { CheckCircle, IdCard, Trash2, Key, Search, X, MessageSquare } from 'lucide-react';
import { tsuData } from '@/lib/tsu-data';
import { deleteStudent, changeStudentPassword, updateStudentNoteAction } from '@/lib/actions';

export default function AdminDashboardClient({ students, programmeManaged }: { students: Student[], programmeManaged: string }) {
  const [searchTerm, setSearchTerm] = useState('');
  const [filterFaculty, setFilterFaculty] = useState('');
  const [filterDept, setFilterDept] = useState('');
  const [filterProg, setFilterProg] = useState('');
  const [filterStatus, setFilterStatus] = useState('');
  const [filterYear, setFilterYear] = useState('');
  const [filterDate, setFilterDate] = useState('');

  // Modals state
  const [studentToDelete, setStudentToDelete] = useState<Student | null>(null);
  const [studentToChangePwd, setStudentToChangePwd] = useState<Student | null>(null);
  const [studentToEditNote, setStudentToEditNote] = useState<Student | null>(null);
  const [newPassword, setNewPassword] = useState('');
  const [newNote, setNewNote] = useState('');
  const [modalLoading, setModalLoading] = useState(false);

  const currentFaculty = useMemo(() => 
    tsuData.find(f => f.faculty === filterFaculty), 
  [filterFaculty]);

  const currentDepartment = useMemo(() => 
    currentFaculty?.departments.find(d => d.name === filterDept), 
  [currentFaculty, filterDept]);

  const filteredStudents = useMemo(() => {
    return students.filter(student => {
      const searchMatch = !searchTerm || 
        student.reg_number.toLowerCase().includes(searchTerm.toLowerCase()) || 
        student.first_name.toLowerCase().includes(searchTerm.toLowerCase()) || 
        student.last_name.toLowerCase().includes(searchTerm.toLowerCase());
        
      const facultyMatch = !filterFaculty || student.faculty === filterFaculty;
      const deptMatch = !filterDept || student.department === filterDept;
      const progMatch = !filterProg || student.programme === filterProg;
      const statusMatch = !filterStatus || student.status === filterStatus;

      // Extracted Year from Reg No (e.g. TSU/SW/2023/123 -> 2023)
      const regParts = student.reg_number.split('/');
      const yearStr = regParts.length >= 3 ? regParts[2] : '';
      const yearMatch = !filterYear || yearStr === filterYear;

      // Date of Registration match (YYYY-MM-DD from ISO)
      const dateStr = student.created_at.split('T')[0];
      const dateMatch = !filterDate || dateStr === filterDate;
      
      return searchMatch && facultyMatch && deptMatch && progMatch && statusMatch && yearMatch && dateMatch;
    });
  }, [students, searchTerm, filterFaculty, filterDept, filterProg, filterStatus, filterYear, filterDate]);

  const handleDelete = async () => {
    if (!studentToDelete) return;
    setModalLoading(true);
    await deleteStudent(studentToDelete.id);
    setStudentToDelete(null);
    setModalLoading(false);
  };

  const handleChangePassword = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!studentToChangePwd || !newPassword) return;
    setModalLoading(true);
    await changeStudentPassword(studentToChangePwd.id, newPassword);
    setStudentToChangePwd(null);
    setNewPassword('');
    setModalLoading(false);
    alert('Password updated successfully.');
  };

  const handleUpdateNote = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!studentToEditNote) return;
    setModalLoading(true);
    await updateStudentNoteAction(studentToEditNote.id, newNote);
    setStudentToEditNote(null);
    setNewNote('');
    setModalLoading(false);
  };

  const availableYears = useMemo(() => {
    const years = students.map(s => {
      const parts = s.reg_number.split('/');
      return parts.length >= 3 ? parts[2] : null;
    }).filter(Boolean) as string[];
    return Array.from(new Set(years)).sort().reverse();
  }, [students]);

  return (
    <>
      <section className="bg-white rounded-xl shadow-sm border border-slate-200 flex flex-col flex-1 overflow-hidden">
        <div className="p-4 border-b border-slate-100 bg-slate-50/50 shrink-0 space-y-4">
          <div className="flex justify-between items-center">
            <h2 className="font-bold text-slate-800 flex items-center gap-2 italic">
              Recent Applications <span className="text-xs font-normal text-slate-500 font-sans not-italic">(Showing: {programmeManaged} Programme)</span>
            </h2>
            <div className="flex gap-2 items-center">
              <span className="bg-green-100 text-green-800 text-xs font-bold px-2.5 py-0.5 rounded">
                {filteredStudents.length} Found
              </span>
            </div>
          </div>
          
          <div className="flex flex-wrap gap-2 items-center text-xs">
            <div className="relative">
              <Search className="absolute left-2.5 top-2 text-slate-400" size={14} />
              <input 
                type="text" 
                placeholder="Search Reg No or Name..." 
                value={searchTerm}
                onChange={e => setSearchTerm(e.target.value)}
                className="pl-8 pr-3 py-1.5 border border-slate-300 rounded focus:outline-none focus:ring-1 focus:ring-green-600 w-48 text-slate-800 bg-white"
              />
            </div>
            
            <select 
              value={filterProg} 
              onChange={e => setFilterProg(e.target.value)}
              className="px-3 py-1.5 border border-slate-300 rounded focus:outline-none focus:ring-1 focus:ring-green-600 text-slate-800 bg-white"
            >
              <option value="">All Programmes</option>
              <option value="Sandwich">Sandwich</option>
              <option value="IDELL">IDELL</option>
            </select>
            
            <select 
              value={filterStatus} 
              onChange={e => setFilterStatus(e.target.value)}
              className="px-3 py-1.5 border border-slate-300 rounded focus:outline-none focus:ring-1 focus:ring-green-600 text-slate-800 bg-white"
            >
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="id_generated">Generated</option>
            </select>

            <select 
              value={filterYear} 
              onChange={e => setFilterYear(e.target.value)}
              className="px-3 py-1.5 border border-slate-300 rounded focus:outline-none focus:ring-1 focus:ring-green-600 text-slate-800 bg-white"
            >
              <option value="">All Years</option>
              {availableYears.map(year => (
                <option key={year} value={year}>{year}</option>
              ))}
            </select>

            <input 
              type="date"
              value={filterDate}
              onChange={e => setFilterDate(e.target.value)}
              className="px-3 py-1.5 border border-slate-300 rounded focus:outline-none focus:ring-1 focus:ring-green-600 text-slate-800 bg-white text-xs"
              title="Filter by Application Date"
            />

            <select 
              value={filterFaculty} 
              onChange={e => {
                setFilterFaculty(e.target.value);
                setFilterDept('');
              }}
              className="px-3 py-1.5 border border-slate-300 rounded focus:outline-none focus:ring-1 focus:ring-green-600 text-slate-800 bg-white w-40 truncate"
            >
              <option value="">All Faculties</option>
              {tsuData.map(f => (
                <option key={f.faculty} value={f.faculty}>{f.faculty}</option>
              ))}
            </select>

            <select 
              value={filterDept} 
              onChange={e => setFilterDept(e.target.value)}
              disabled={!filterFaculty}
              className="px-3 py-1.5 border border-slate-300 rounded focus:outline-none focus:ring-1 focus:ring-green-600 text-slate-800 bg-white w-32 truncate disabled:bg-slate-100 disabled:text-slate-400"
            >
              <option value="">All Depts</option>
              {currentFaculty?.departments.map(d => (
                <option key={d.name} value={d.name}>{d.name}</option>
              ))}
            </select>
            
            {(searchTerm || filterFaculty || filterDept || filterProg || filterStatus || filterYear || filterDate) && (
               <button 
                 onClick={() => {
                   setSearchTerm('');
                   setFilterFaculty('');
                   setFilterDept('');
                   setFilterProg('');
                   setFilterStatus('');
                   setFilterYear('');
                   setFilterDate('');
                 }}
                 className="text-red-500 hover:text-red-700 font-bold ml-2 underline flex items-center"
               >
                 <X size={12} className="mr-0.5" /> Clear
               </button>
            )}
          </div>
        </div>

        {filteredStudents.length === 0 ? (
          <div className="p-8 text-center text-slate-500 text-sm">
            No students found matching your filters.
          </div>
        ) : (
          <div className="flex-1 overflow-y-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-y border-slate-100">
                  <th className="px-4 py-2 sticky top-0 bg-slate-50 shadow-sm">Student Name</th>
                  <th className="px-4 py-2 sticky top-0 bg-slate-50 shadow-sm">Reg Number</th>
                  <th className="px-4 py-2 sticky top-0 bg-slate-50 shadow-sm">Faculty/Dept</th>
                  <th className="px-4 py-2 sticky top-0 bg-slate-50 shadow-sm">Status</th>
                  <th className="px-4 py-2 text-right sticky top-0 bg-slate-50 shadow-sm">Actions</th>
                </tr>
              </thead>
              <tbody className="text-xs text-slate-700">
                {filteredStudents.map((student) => (
                  <tr key={student.id} className="border-b border-slate-50 hover:bg-green-50/30 transition-colors">
                    <td className="px-4 py-3 font-medium">
                      <div className="flex items-center">
                        <img className="h-8 w-8 rounded object-cover border border-slate-200" src={student.passport_photo} alt="" />
                        <div className="ml-3 leading-tight">
                          <div className="font-bold text-slate-900 uppercase">
                            {student.last_name}, {student.first_name} {student.middle_name}
                          </div>
                          <div className="text-[10px] text-slate-500 uppercase mt-0.5 font-bold">Blood: {student.blood_group}</div>
                        </div>
                      </div>
                    </td>
                    <td className="px-4 py-3 font-mono text-slate-600">
                      {student.reg_number}
                    </td>
                    <td className="px-4 py-3 text-[11px] uppercase">
                      <div className="font-bold text-slate-700">{student.faculty}</div>
                      <div className="text-slate-500">{student.course_of_study || student.department}</div>
                    </td>
                    <td className="px-4 py-3">
                      {student.status === 'id_generated' ? (
                        <span className="inline-flex items-center text-[10px] font-bold text-green-700 bg-green-50 px-2 py-0.5 rounded border border-green-100 uppercase tracking-wide">
                          <CheckCircle size={12} className="mr-1" /> Generated
                        </span>
                      ) : (
                        <span className="inline-flex items-center text-[10px] font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded border border-slate-200 uppercase tracking-wide">
                          Pending
                        </span>
                      )}
                    </td>
                    <td className="px-4 py-3 text-right">
                      <div className="flex items-center justify-end gap-2">
                        <button 
                          title="Add Note/Message"
                          onClick={() => {
                            setStudentToEditNote(student);
                            setNewNote(student.admin_note || '');
                          }}
                          className="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition-colors"
                        >
                          <MessageSquare size={14} />
                        </button>
                        <button 
                          title="Change Password"
                          onClick={() => setStudentToChangePwd(student)}
                          className="p-1.5 text-amber-600 hover:bg-amber-50 rounded transition-colors"
                        >
                          <Key size={14} />
                        </button>
                        <button 
                          title="Delete Student"
                          onClick={() => setStudentToDelete(student)}
                          className="p-1.5 text-red-600 hover:bg-red-50 rounded transition-colors"
                        >
                          <Trash2 size={14} />
                        </button>
                        <Link 
                          href={`/admin/id-card/${encodeURIComponent(student.id)}`}
                          className="bg-green-100 hover:bg-green-200 text-green-800 px-3 py-1.5 rounded font-bold transition-colors inline-block text-[10px] uppercase ml-2"
                        >
                          Generate ID
                        </Link>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>

      {/* Delete Modal */}
      {studentToDelete && (
        <div className="fixed inset-0 bg-slate-900/50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-lg shadow-xl p-6 max-w-sm w-full">
            <h3 className="text-lg font-bold text-slate-800 uppercase tracking-tight mb-2">Confirm Deletion</h3>
            <p className="text-sm text-slate-600 mb-6">
              Are you sure you want to delete <span className="font-bold">{studentToDelete.reg_number}</span>? This action cannot be undone.
            </p>
            <div className="flex justify-end gap-3">
              <button 
                onClick={() => setStudentToDelete(null)}
                className="px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded text-sm hover:bg-slate-200"
              >
                CANCEL
              </button>
              <button 
                onClick={handleDelete}
                disabled={modalLoading}
                className="px-4 py-2 bg-red-600 text-white font-bold rounded text-sm hover:bg-red-700 disabled:opacity-50"
              >
                {modalLoading ? 'DELETING...' : 'DELETE'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Password Modal */}
      {studentToChangePwd && (
        <div className="fixed inset-0 bg-slate-900/50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-lg shadow-xl p-6 max-w-sm w-full">
            <h3 className="text-lg font-bold text-slate-800 uppercase tracking-tight mb-2">Change Password</h3>
            <p className="text-xs text-slate-600 mb-4">
              Updating password for <span className="font-bold">{studentToChangePwd.reg_number}</span>.
            </p>
            <form onSubmit={handleChangePassword}>
              <div className="mb-6">
                <label className="block text-[10px] uppercase font-bold text-slate-500 mb-1">New Password</label>
                <input 
                  type="text" 
                  value={newPassword}
                  onChange={e => setNewPassword(e.target.value)}
                  placeholder="Enter new password"
                  required
                  className="w-full px-3 py-2 border border-slate-300 rounded focus:outline-none focus:ring-1 focus:ring-green-600 text-sm text-slate-800 font-mono"
                />
              </div>
              <div className="flex justify-end gap-3">
                <button 
                  type="button"
                  onClick={() => { setStudentToChangePwd(null); setNewPassword(''); }}
                  className="px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded text-sm hover:bg-slate-200"
                >
                  CANCEL
                </button>
                <button 
                  type="submit"
                  disabled={modalLoading || !newPassword}
                  className="px-4 py-2 bg-green-600 text-white font-bold rounded text-sm hover:bg-green-700 disabled:opacity-50"
                >
                  {modalLoading ? 'UPDATING...' : 'UPDATE'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Note Modal */}
      {studentToEditNote && (
        <div className="fixed inset-0 bg-slate-900/50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-lg shadow-xl p-6 max-w-md w-full">
            <h3 className="text-lg font-bold text-slate-800 uppercase tracking-tight mb-2">Requirement & Notes</h3>
            <p className="text-xs text-slate-600 mb-4">
              Action required or message for <span className="font-bold">{studentToEditNote.reg_number}</span>. Leave empty to clear.
            </p>
            <form onSubmit={handleUpdateNote}>
              <div className="mb-6">
                <label className="block text-[10px] uppercase font-bold text-slate-500 mb-1">Admin Message</label>
                <textarea 
                  value={newNote}
                  onChange={e => setNewNote(e.target.value)}
                  placeholder="E.g., Passport photo rejected because of blurry background. Please provide a clearer photo."
                  rows={4}
                  className="w-full px-3 py-2 border border-slate-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-600 text-sm text-slate-800 resize-none"
                />
              </div>
              <div className="flex justify-end gap-3">
                <button 
                  type="button"
                  onClick={() => { setStudentToEditNote(null); setNewNote(''); }}
                  className="px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded text-sm hover:bg-slate-200"
                >
                  CANCEL
                </button>
                <button 
                  type="submit"
                  disabled={modalLoading}
                  className="px-4 py-2 bg-blue-600 text-white font-bold rounded text-sm hover:bg-blue-700 disabled:opacity-50"
                >
                  {modalLoading ? 'SAVING...' : 'SAVE NOTE'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </>
  );
}
